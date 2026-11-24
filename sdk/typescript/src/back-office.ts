/**
 * Back Office: keep a Back Office store's FluentCRM in step with payments taken somewhere else.
 *
 * A site or app built outside WordPress takes its own payments with Stripe. Its server checks each Stripe webhook
 * with Stripe's own SDK (`stripe.webhooks.constructEvent`), does its own work, then turns the event into a contact
 * update for FluentCRM's incoming webhook (FluentCRM > Settings > Incoming Webhooks):
 *
 * ```ts
 * const event = stripe.webhooks.constructEvent(rawBody, signature, endpointSecret);
 * const update = contactUpdateFromStripe(event);
 * if (update) await sendContactUpdate(process.env.FLUENTCRM_WEBHOOK_URL!, update);
 * ```
 *
 * FluentCRM has no official JavaScript SDK, so `sendContactUpdate` posts the JSON itself. Tags go by name; FluentCRM
 * creates a tag the first time it sees it, and a "Tag Applied" automation on the store can start emails from it.
 */

/** The parts of a Stripe event this reads. A `Stripe.Event` from the `stripe` package fits. */
export interface StripeEventLike {
  type: string;
  /** When Stripe made the event, in Unix seconds. */
  created?: number;
  data: { object: object; previous_attributes?: object };
}

/** The tag names used for each state. Change any to match the tags already in your FluentCRM. */
export interface BackOfficeTagNames {
  customer: string;
  trial: string;
  trialEnding: string;
  cancelled: string;
  refunded: string;
  paymentFailed: string;
  pastDue: string;
  checkoutAbandoned: string;
  disputed: string;
}

export const DEFAULT_TAG_NAMES: BackOfficeTagNames = {
  customer: 'Customer',
  trial: 'Trial',
  trialEnding: 'Trial Ending',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
  paymentFailed: 'Payment Failed',
  pastDue: 'Past Due',
  checkoutAbandoned: 'Checkout Abandoned',
  disputed: 'Disputed',
};

export interface ContactUpdateOptions {
  /** Tag names to use instead of the defaults. */
  tags?: Partial<BackOfficeTagNames>;
  /**
   * The plan's name, added as a tag when someone pays and taken off when they cancel. Defaults to the Stripe object's
   * `metadata.plan`, so setting `metadata: { plan: 'Pro' }` on the Checkout Session and subscription is enough.
   */
  plan?: string;
  /**
   * The customer's email, for events that don't carry one: the `customer.subscription.*` events and
   * `charge.dispute.created` only have ids. Look it up in your own database (or with `stripe.customers.retrieve`).
   */
  email?: string;
  /**
   * When each of this customer's tags was last added or taken off: the event's `created` time (Unix seconds), by tag
   * name. Stripe doesn't promise to send events in order, and retries a failed one later, so a tag an older event
   * would change (say Customer, from a payment that arrives after the cancel) is left alone instead of undoing the
   * newer change. Tags the newer events didn't touch still change. After each update, save `event.created` for every
   * tag in its `tags` and `detach_tags` (`tagTimes()` does this).
   */
  tagsChangedAt?: Record<string, number>;
}

/** The body FluentCRM's incoming webhook takes. */
export interface ContactUpdate {
  email: string;
  first_name?: string;
  last_name?: string;
  tags?: string[];
  detach_tags?: string[];
}

type Obj = Record<string, unknown>;

const text = (value: unknown): string | undefined => (typeof value === 'string' && value.trim() !== '' ? value.trim() : undefined);
const child = (value: unknown, key: string): unknown => (value && typeof value === 'object' ? (value as Obj)[key] : undefined);
const list = (...names: (string | undefined)[]): string[] => [...new Set(names.filter((name): name is string => !!name))];

/** What one event means for the contact; `email` is left out when the event carries none. */
interface Change {
  email?: string;
  name?: string;
  add: string[];
  remove: string[];
  /** Skip the update when the event has no email (rather than throwing), e.g. an expired checkout with no email typed. */
  optional?: boolean;
}

interface Context {
  object: Obj;
  previous: Obj;
  tags: BackOfficeTagNames;
  plan?: string;
}

const checkoutEmail = (object: Obj) => text(child(object.customer_details, 'email')) ?? text(object.customer_email);

function paidCheckout({ object, tags, plan }: Context, completed: boolean): Change | null {
  // A session can complete before a delayed payment (bank debits) clears: Stripe then sends
  // async_payment_succeeded when it does. A setup-mode session saves a card and takes no payment.
  if (object.mode === 'setup') return null;
  if (completed && object.payment_status === 'unpaid') return null;
  return {
    email: checkoutEmail(object),
    name: text(child(object.customer_details, 'name')),
    add: list(tags.customer, plan),
    remove: list(tags.trial, tags.trialEnding, tags.cancelled, tags.checkoutAbandoned),
  };
}

/**
 * A plan change: Stripe puts the old metadata in previous_attributes.metadata. A first plan (no old one) is added, and
 * a plan taken off just loses its tag.
 */
function planSwap(previous: Obj, plan?: string): [string[], string[]] {
  if (!('metadata' in previous)) return [[], []];
  const oldPlan = text(child(previous.metadata, 'plan'));
  if (oldPlan === plan) return [[], []];
  return [plan ? [plan] : [], oldPlan ? [oldPlan] : []];
}

/** A status change: late payments add Past Due, and an active subscription clears it. */
function statusSwap(object: Obj, previous: Obj, tags: BackOfficeTagNames): [string[], string[]] {
  if (!('status' in previous)) return [[], []];
  if (object.status === 'past_due' || object.status === 'unpaid') return [[tags.pastDue], []];
  return object.status === 'active' ? [[], [tags.pastDue, tags.paymentFailed]] : [[], []];
}

function subscriptionUpdated({ object, previous, tags, plan }: Context): Change | null {
  const [planAdd, planRemove] = planSwap(previous, plan);
  const [statusAdd, statusRemove] = statusSwap(object, previous, tags);
  const add = list(...planAdd, ...statusAdd);
  const remove = list(...planRemove, ...statusRemove);
  return add.length > 0 || remove.length > 0 ? { add, remove } : null;
}

const HANDLERS: Record<string, (context: Context) => Change | null> = {
  'checkout.session.completed': (c) => paidCheckout(c, true),
  'checkout.session.async_payment_succeeded': (c) => paidCheckout(c, false),
  'checkout.session.expired': ({ object, tags }) =>
    object.mode === 'setup' ? null : { email: checkoutEmail(object), add: [tags.checkoutAbandoned], remove: [], optional: true },
  'invoice.paid': ({ object, tags }) => ({
    email: text(object.customer_email),
    add: [tags.customer],
    remove: list(tags.cancelled, tags.paymentFailed, tags.pastDue),
  }),
  'invoice.payment_failed': ({ object, tags }) => ({ email: text(object.customer_email), add: [tags.paymentFailed], remove: [] }),
  'customer.subscription.updated': subscriptionUpdated,
  'customer.subscription.trial_will_end': ({ tags }) => ({ add: [tags.trialEnding], remove: [] }),
  'customer.subscription.deleted': ({ tags, plan }) => ({ add: [tags.cancelled], remove: list(tags.customer, plan, tags.pastDue) }),
  // A partial refund leaves the customer a customer.
  'charge.refunded': ({ object, tags }) =>
    object.refunded === true
      ? { email: text(child(object.billing_details, 'email')) ?? text(object.receipt_email), add: [tags.refunded], remove: [tags.customer] }
      : null,
  'charge.dispute.created': ({ tags }) => ({ add: [tags.disputed], remove: [] }),
};

/**
 * Turns a Stripe event into a FluentCRM contact update, or `null` for events that don't change a contact's tags.
 *
 * | Stripe event | Tags added | Tags taken off |
 * |---|---|---|
 * | `checkout.session.completed` (paid), `checkout.session.async_payment_succeeded` | Customer, the plan | Trial, Trial Ending, Cancelled, Checkout Abandoned |
 * | `checkout.session.expired` (with an email) | Checkout Abandoned | |
 * | `invoice.paid` | Customer | Cancelled, Payment Failed, Past Due |
 * | `invoice.payment_failed` | Payment Failed | |
 * | `customer.subscription.updated` (plan or status changed) | the new plan; Past Due | the old plan; Past Due and Payment Failed once active |
 * | `customer.subscription.trial_will_end` | Trial Ending | |
 * | `customer.subscription.deleted` | Cancelled | Customer, the plan, Past Due |
 * | `charge.refunded` (refunded in full) | Refunded | Customer |
 * | `charge.dispute.created` | Disputed | |
 */
export function contactUpdateFromStripe(event: StripeEventLike, options: ContactUpdateOptions = {}): ContactUpdate | null {
  const object = event.data.object as Obj;
  const context: Context = {
    object,
    previous: (event.data.previous_attributes ?? {}) as Obj,
    tags: { ...DEFAULT_TAG_NAMES, ...options.tags },
    plan: text(options.plan) ?? text(child(object.metadata, 'plan')),
  };
  const change = HANDLERS[event.type]?.(context);
  if (!change) return null;
  // A tag a newer event already changed stays as that event left it.
  const current = (tag: string) => event.created === undefined || event.created >= (options.tagsChangedAt?.[tag] ?? -Infinity);
  change.add = change.add.filter(current);
  change.remove = change.remove.filter(current);
  if (change.add.length === 0 && change.remove.length === 0) return null;

  const address = text(options.email) ?? change.email;
  if (!address) {
    if (change.optional) return null;
    throw new Error(`Stripe's ${event.type} event has no email for this customer: look it up and pass it as options.email.`);
  }
  const result: ContactUpdate = { email: address.toLowerCase() };
  if (change.name) {
    const [first, ...rest] = change.name.split(/\s+/);
    result.first_name = first;
    if (rest.length > 0) result.last_name = rest.join(' ');
  }
  if (change.add.length > 0) result.tags = change.add;
  if (change.remove.length > 0) result.detach_tags = change.remove;
  return result;
}

/**
 * The times to save after sending an update: `created` for every tag it adds or takes off, merged into the customer's
 * `tagsChangedAt`.
 */
export function tagTimes(update: ContactUpdate, created: number, tagsChangedAt: Record<string, number> = {}): Record<string, number> {
  const times = { ...tagsChangedAt };
  for (const tag of [...(update.tags ?? []), ...(update.detach_tags ?? [])]) times[tag] = Math.max(created, times[tag] ?? created);
  return times;
}

export interface SendContactUpdateOptions {
  /** A fetch to use instead of the global one (for tests, or a fetch with retries). */
  fetch?: typeof fetch;
}

const LOCAL_HOSTS = new Set(['localhost', '127.0.0.1', '[::1]']);

/** FluentCRM's answer: WordPress's JSON envelope, `{ success, data }`, with any error message under `data.message`. */
function readAnswer(body: unknown): { success: boolean; message?: string } | null {
  if (!body || typeof body !== 'object' || typeof (body as Obj).success !== 'boolean') return null;
  const { success, data } = body as { success: boolean; data?: unknown };
  return { success, message: text(child(data, 'message')) ?? text((body as Obj).message) };
}

/**
 * Sends a contact update to FluentCRM's incoming webhook. The webhook's address is its secret: keep it on your server.
 * It must be https (plain http only for a site on this computer), and redirects are refused so the address and the
 * contact never go anywhere else. Throws unless FluentCRM confirms the update, so your Stripe webhook handler can
 * answer with an error and Stripe retries.
 */
export async function sendContactUpdate(webhookUrl: string, update: ContactUpdate, options: SendContactUpdateOptions = {}): Promise<void> {
  const url = new URL(webhookUrl);
  if (url.protocol !== 'https:' && !(url.protocol === 'http:' && LOCAL_HOSTS.has(url.hostname))) {
    throw new Error('The FluentCRM webhook address must start with https://.');
  }
  const send = options.fetch ?? fetch;
  const response = await send(url.href, {
    method: 'POST',
    headers: { 'content-type': 'application/json', accept: 'application/json' },
    body: JSON.stringify(update),
    redirect: 'error',
  });
  const answer = readAnswer(await response.json().catch(() => null));
  if (!response.ok || answer?.success !== true) {
    const reason = answer?.message ?? (answer ? '' : 'no answer from FluentCRM; is the plugin on and the address right?');
    throw new Error(`FluentCRM did not take the contact update (${response.status}${reason ? `: ${reason}` : ''}).`);
  }
}
