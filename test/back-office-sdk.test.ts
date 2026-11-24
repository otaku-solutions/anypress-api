// The SDK's Back Office helper (sdk/typescript/src/back-office.ts): Stripe events, signed and checked by Stripe's own
// SDK as an app's webhook handler would, turned into FluentCRM incoming-webhook updates.
import Stripe from 'stripe';
import { describe, expect, it, vi } from 'vitest';
import { contactUpdateFromStripe, sendContactUpdate } from '../sdk/typescript/src/index.ts';

const stripe = new Stripe('sk_test_back_office');
const SECRET = 'whsec_back_office_test';

/** What an app's handler gets: a payload signed by Stripe, checked with constructEvent. */
function received(type: string, object: object): Stripe.Event {
  const payload = JSON.stringify({ id: `evt_${type.replace(/\W/g, '_')}`, object: 'event', type, data: { object } });
  const header = stripe.webhooks.generateTestHeaderString({ payload, secret: SECRET });
  return stripe.webhooks.constructEvent(payload, header, SECRET);
}

describe('contactUpdateFromStripe', () => {
  it('a paid checkout adds Customer and the plan, and takes off the trial, cancelled and abandoned tags', () => {
    const event = received('checkout.session.completed', {
      object: 'checkout.session',
      payment_status: 'paid',
      customer_details: { email: 'Ava@Anypress.example', name: 'Ava Reyes' },
      metadata: { plan: 'Pro' },
    });
    expect(contactUpdateFromStripe(event)).toEqual({
      email: 'ava@anypress.example',
      first_name: 'Ava',
      last_name: 'Reyes',
      tags: ['Customer', 'Pro'],
      detach_tags: ['Trial', 'Trial Ending', 'Cancelled', 'Checkout Abandoned'],
    });
  });

  it('waits for a delayed payment to clear, then tags on async_payment_succeeded', () => {
    const session = { mode: 'payment', payment_status: 'unpaid', customer_details: { email: 'ava@anypress.example' }, metadata: { plan: 'Pro' } };
    expect(contactUpdateFromStripe(received('checkout.session.completed', session))).toBeNull();
    const cleared = received('checkout.session.async_payment_succeeded', { ...session, payment_status: 'paid' });
    expect(contactUpdateFromStripe(cleared)).toEqual({ email: 'ava@anypress.example', tags: ['Customer', 'Pro'], detach_tags: ['Trial', 'Trial Ending', 'Cancelled', 'Checkout Abandoned'] });
  });

  it('leaves a setup-mode checkout (a saved card, no payment) alone', () => {
    const event = received('checkout.session.completed', { mode: 'setup', payment_status: 'no_payment_required', customer_details: { email: 'ava@anypress.example' } });
    expect(contactUpdateFromStripe(event)).toBeNull();
  });

  it('a paid invoice keeps Customer and takes off Cancelled, Payment Failed and Past Due; a failed one adds Payment Failed', () => {
    const event = received('invoice.paid', { object: 'invoice', customer_email: 'ava@anypress.example' });
    expect(contactUpdateFromStripe(event)).toEqual({ email: 'ava@anypress.example', tags: ['Customer'], detach_tags: ['Cancelled', 'Payment Failed', 'Past Due'] });
    const failed = received('invoice.payment_failed', { object: 'invoice', customer_email: 'ava@anypress.example' });
    expect(contactUpdateFromStripe(failed)).toEqual({ email: 'ava@anypress.example', tags: ['Payment Failed'] });
  });

  it('a subscription change swaps the plan tag, and tags Past Due until it is active again', () => {
    const email = 'ava@anypress.example';
    const upgrade = received('customer.subscription.updated', { object: 'subscription', status: 'active', metadata: { plan: 'Team' } });
    (upgrade.data as { previous_attributes?: object }).previous_attributes = { metadata: { plan: 'Pro' } };
    expect(contactUpdateFromStripe(upgrade, { email })).toEqual({ email, tags: ['Team'], detach_tags: ['Pro'] });

    const late = received('customer.subscription.updated', { object: 'subscription', status: 'past_due', metadata: { plan: 'Pro' } });
    (late.data as { previous_attributes?: object }).previous_attributes = { status: 'active' };
    expect(contactUpdateFromStripe(late, { email })).toEqual({ email, tags: ['Past Due'] });

    const back = received('customer.subscription.updated', { object: 'subscription', status: 'active', metadata: { plan: 'Pro' } });
    (back.data as { previous_attributes?: object }).previous_attributes = { status: 'past_due' };
    expect(contactUpdateFromStripe(back, { email })).toEqual({ email, detach_tags: ['Past Due', 'Payment Failed'] });

    const cleared = received('customer.subscription.updated', { object: 'subscription', status: 'active', metadata: {} });
    (cleared.data as { previous_attributes?: object }).previous_attributes = { metadata: { plan: 'Pro' } };
    expect(contactUpdateFromStripe(cleared, { email })).toEqual({ email, detach_tags: ['Pro'] });

    const quiet = received('customer.subscription.updated', { object: 'subscription', status: 'active', metadata: { plan: 'Pro' } });
    (quiet.data as { previous_attributes?: object }).previous_attributes = { cancel_at_period_end: false };
    expect(contactUpdateFromStripe(quiet, { email })).toBeNull();
  });

  it('tags Trial Ending three days before a trial ends, with the email passed in', () => {
    const event = received('customer.subscription.trial_will_end', { object: 'subscription', customer: 'cus_1', status: 'trialing' });
    expect(() => contactUpdateFromStripe(event)).toThrow(/options\.email/);
    expect(contactUpdateFromStripe(event, { email: 'ava@anypress.example' })).toEqual({ email: 'ava@anypress.example', tags: ['Trial Ending'] });
  });

  it('tags an expired checkout as abandoned when the shopper typed an email, and skips it when they did not', () => {
    const typed = received('checkout.session.expired', { object: 'checkout.session', mode: 'subscription', customer_details: { email: 'ava@anypress.example' } });
    expect(contactUpdateFromStripe(typed)).toEqual({ email: 'ava@anypress.example', tags: ['Checkout Abandoned'] });
    expect(contactUpdateFromStripe(received('checkout.session.expired', { object: 'checkout.session', mode: 'payment' }))).toBeNull();
    expect(contactUpdateFromStripe(received('checkout.session.expired', { mode: 'setup', customer_email: 'ava@anypress.example' }))).toBeNull();
  });

  it('tags a dispute, with the email passed in', () => {
    const event = received('charge.dispute.created', { object: 'dispute', charge: 'ch_1', reason: 'fraudulent' });
    expect(() => contactUpdateFromStripe(event)).toThrow(/options\.email/);
    expect(contactUpdateFromStripe(event, { email: 'ava@anypress.example' })).toEqual({ email: 'ava@anypress.example', tags: ['Disputed'] });
  });

  it('a cancelled subscription needs the email passed in, then swaps Customer and the plan for Cancelled', () => {
    const event = received('customer.subscription.deleted', { object: 'subscription', customer: 'cus_1', metadata: { plan: 'Pro' } });
    expect(() => contactUpdateFromStripe(event)).toThrow(/options\.email/);
    expect(contactUpdateFromStripe(event, { email: 'ava@anypress.example' })).toEqual({
      email: 'ava@anypress.example',
      tags: ['Cancelled'],
      detach_tags: ['Customer', 'Pro', 'Past Due'],
    });
  });

  it('a full refund adds Refunded and takes off Customer; a partial one changes nothing', () => {
    const full = received('charge.refunded', { object: 'charge', refunded: true, billing_details: { email: 'ava@anypress.example' } });
    expect(contactUpdateFromStripe(full)).toEqual({ email: 'ava@anypress.example', tags: ['Refunded'], detach_tags: ['Customer'] });
    const partial = received('charge.refunded', { object: 'charge', refunded: false, receipt_email: 'ava@anypress.example' });
    expect(contactUpdateFromStripe(partial)).toBeNull();
  });

  it('skips an event older than the last one applied, so a late payment does not undo a cancel', () => {
    const paid = { ...received('invoice.paid', { customer_email: 'ava@anypress.example' }), created: 1_700_000_000 };
    expect(contactUpdateFromStripe(paid, { lastEventAt: 1_700_000_100 })).toBeNull();
    expect(contactUpdateFromStripe(paid, { lastEventAt: 1_700_000_000 })?.tags).toEqual(['Customer']);
    expect(contactUpdateFromStripe(paid, { lastEventAt: 1_699_999_000 })?.tags).toEqual(['Customer']);
  });

  it('uses the tag names and plan it is given, and ignores other events', () => {
    const event = received('invoice.paid', { customer_email: 'ava@anypress.example' });
    expect(contactUpdateFromStripe(event, { tags: { customer: 'Paying' } })?.tags).toEqual(['Paying']);
    const checkout = received('checkout.session.completed', { payment_status: 'paid', customer_email: 'ava@anypress.example' });
    expect(contactUpdateFromStripe(checkout, { plan: 'Team' })?.tags).toEqual(['Customer', 'Team']);
    expect(contactUpdateFromStripe(received('customer.created', { email: 'ava@anypress.example' }))).toBeNull();
  });

  it('refuses a payload whose signature does not match, before the helper sees it', () => {
    const payload = JSON.stringify({ id: 'evt_x', object: 'event', type: 'invoice.paid', data: { object: {} } });
    const header = stripe.webhooks.generateTestHeaderString({ payload, secret: 'whsec_someone_else' });
    expect(() => stripe.webhooks.constructEvent(payload, header, SECRET)).toThrow();
  });
});

describe('sendContactUpdate', () => {
  const update = { email: 'ava@anypress.example', tags: ['Customer'] };

  it('posts the update as JSON to the webhook address', async () => {
    const fetch = vi.fn(async () => new Response(JSON.stringify({ success: true, data: { message: 'Subscriber has been updated' } }), { status: 200 }));
    await sendContactUpdate('https://hub.anypress.example/?fluentcrm=1&route=contact&hash=abc', update, { fetch });
    const [url, init] = fetch.mock.calls[0] as unknown as [string, RequestInit];
    expect(url).toContain('route=contact');
    expect(init.method).toBe('POST');
    expect(init.redirect).toBe('error');
    expect(JSON.parse(init.body as string)).toEqual(update);
  });

  it('throws when FluentCRM turns it away, so Stripe retries', async () => {
    const refused = vi.fn(async () => new Response(JSON.stringify({ success: false, data: { message: 'Invalid Webhook Hash' } }), { status: 200 }));
    await expect(sendContactUpdate('https://hub.anypress.example/', update, { fetch: refused })).rejects.toThrow(/Invalid Webhook Hash/);
    const down = vi.fn(async () => new Response('Bad Gateway', { status: 502 }));
    await expect(sendContactUpdate('https://hub.anypress.example/', update, { fetch: down })).rejects.toThrow(/502/);
    const page = vi.fn(async () => new Response('<html>Not Found</html>', { status: 200 }));
    await expect(sendContactUpdate('https://hub.anypress.example/', update, { fetch: page })).rejects.toThrow(/no answer from FluentCRM/);
  });

  it('only sends over https, apart from a site on this computer', async () => {
    const fetch = vi.fn(async () => new Response(JSON.stringify({ success: true }), { status: 200 }));
    await expect(sendContactUpdate('http://hub.anypress.example/?hash=abc', update, { fetch })).rejects.toThrow(/https/);
    expect(fetch).not.toHaveBeenCalled();
    await sendContactUpdate('http://127.0.0.1:9431/?hash=abc', update, { fetch });
    expect(fetch).toHaveBeenCalledOnce();
  });
});
