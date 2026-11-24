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
  data: { object: object };
}

/** The tag names used for each state. Change any to match the tags already in your FluentCRM. */
export interface BackOfficeTagNames {
  customer: string;
  trial: string;
  cancelled: string;
  refunded: string;
}

export const DEFAULT_TAG_NAMES: BackOfficeTagNames = { customer: 'Customer', trial: 'Trial', cancelled: 'Cancelled', refunded: 'Refunded' };

export interface ContactUpdateOptions {
  /** Tag names to use instead of the defaults. */
  tags?: Partial<BackOfficeTagNames>;
  /**
   * The plan's name, added as a tag when someone pays and taken off when they cancel. Defaults to the Stripe object's
   * `metadata.plan`, so setting `metadata: { plan: 'Pro' }` on the Checkout Session or subscription is enough.
   */
  plan?: string;
  /**
   * The customer's email, for events that don't carry one (`customer.subscription.deleted` only has the customer id:
   * look it up with `stripe.customers.retrieve` or in your own database).
   */
  email?: string;
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

/**
 * Turns a Stripe event into a FluentCRM contact update, or `null` for events that don't change a contact's tags.
 *
 * | Stripe event | Tags added | Tags taken off |
 * |---|---|---|
 * | `checkout.session.completed` (paid) | Customer, the plan | Trial, Cancelled |
 * | `invoice.paid` | Customer | Cancelled |
 * | `customer.subscription.deleted` | Cancelled | Customer, the plan |
 * | `charge.refunded` (refunded in full) | Refunded | Customer |
 */
export function contactUpdateFromStripe(event: StripeEventLike, options: ContactUpdateOptions = {}): ContactUpdate | null {
  const tags = { ...DEFAULT_TAG_NAMES, ...options.tags };
  const object = event.data.object as Obj;
  const plan = text(options.plan) ?? text(child(object.metadata, 'plan'));

  const update = (email: string | undefined, add: string[], remove: string[], name?: string): ContactUpdate => {
    const address = text(options.email) ?? email;
    if (!address) {
      throw new Error(`Stripe's ${event.type} event has no email for this customer: look it up and pass it as options.email.`);
    }
    const result: ContactUpdate = { email: address.toLowerCase() };
    if (name) {
      const [first, ...rest] = name.split(/\s+/);
      result.first_name = first;
      if (rest.length > 0) result.last_name = rest.join(' ');
    }
    if (add.length > 0) result.tags = add;
    if (remove.length > 0) result.detach_tags = remove;
    return result;
  };

  switch (event.type) {
    case 'checkout.session.completed': {
      // A session can complete before an async payment (bank debits) clears; wait for the paid one then.
      if (object.payment_status === 'unpaid') return null;
      const details = object.customer_details;
      return update(
        text(child(details, 'email')) ?? text(object.customer_email),
        list(tags.customer, plan),
        list(tags.trial, tags.cancelled),
        text(child(details, 'name')),
      );
    }
    case 'invoice.paid':
      return update(text(object.customer_email), list(tags.customer), list(tags.cancelled));
    case 'customer.subscription.deleted':
      return update(undefined, list(tags.cancelled), list(tags.customer, plan));
    case 'charge.refunded':
      // A partial refund leaves the customer a customer.
      if (object.refunded !== true) return null;
      return update(text(child(object.billing_details, 'email')) ?? text(object.receipt_email), list(tags.refunded), list(tags.customer));
    default:
      return null;
  }
}

export interface SendContactUpdateOptions {
  /** A fetch to use instead of the global one (for tests, or a fetch with retries). */
  fetch?: typeof fetch;
}

/**
 * Sends a contact update to FluentCRM's incoming webhook. The webhook's address is its secret: keep it on your server.
 * Throws when FluentCRM doesn't take it, so your Stripe webhook handler can answer with an error and Stripe retries.
 */
export async function sendContactUpdate(webhookUrl: string, update: ContactUpdate, options: SendContactUpdateOptions = {}): Promise<void> {
  const send = options.fetch ?? fetch;
  const response = await send(webhookUrl, {
    method: 'POST',
    headers: { 'content-type': 'application/json', accept: 'application/json' },
    body: JSON.stringify(update),
  });
  const body = (await response.json().catch(() => null)) as { success?: boolean; message?: string } | null;
  if (!response.ok || body?.success === false) {
    throw new Error(`FluentCRM did not take the contact update (${response.status}${body?.message ? `: ${body.message}` : ''}).`);
  }
}
