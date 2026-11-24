# n8n-nodes-anypress

n8n nodes for [AnyPress](https://anypress.io): create a client's WordPress site from a form, a payment or a CRM step, and start a workflow the moment the site is ready.

- **AnyPress**: create, get, suspend, resume and delete sites.
- **AnyPress Trigger**: starts a workflow when a site is ready, fails, is suspended, resumed, deleted, restored or backed up. It registers its own webhook with AnyPress when the workflow is turned on, removes it when the workflow is turned off, and checks the signature on every request.

## Install

In n8n, open **Settings**, **Community nodes**, **Install**, and enter `n8n-nodes-anypress`. See n8n's [community nodes guide](https://docs.n8n.io/integrations/community-nodes/installation/) for self-hosted installs.

## Credentials

Add an **AnyPress API** credential:

| Field | What to enter |
|---|---|
| API Key | A key from [app.anypress.io](https://app.anypress.io) under **API keys**, or a partner key for a reseller account |
| API Secret | The secret shown once when the key was made |
| API Address | Leave it as `https://api.anypress.io` |

A storefront's own key works on that storefront. A partner key works on every storefront in a reseller account: pick the storefront on each node.

## Operations

| Operation | What it does |
|---|---|
| Create | Builds a site from your starting site. **Your Reference** (an order or deal number, say) makes a repeat create answer with the site already made, so a retried workflow never builds two. Turn on **Wait Until Ready** to wait for the build, or use the trigger |
| Get | The site, its status and its address, by your reference or its site ID |
| Suspend | Takes the site offline, backing it up first |
| Resume | Brings it back online |
| Delete | Deletes it. Its backups are kept, so it can be restored |

## Trigger

Pick the events to listen for (Site Ready by default). Each item has the event, its id (the same on every retry, so you can drop repeats), the time, and the site with its `url`.

AnyPress only sends to `https://` addresses on the public internet, so the trigger needs an n8n that is reachable from outside, such as n8n Cloud or a self-hosted n8n behind its own domain.

## Workflow: Stripe payments into a Back Office store

A site with no server of its own (a site builder, a static site) can still keep a Back Office store's FluentCRM in step with its Stripe payments. [workflows/back-office-stripe.json](workflows/back-office-stripe.json) is a ready-made n8n workflow for this, using n8n's own Stripe nodes. No AnyPress node is needed. Each payment becomes tags on the customer's contact, and a "Tag Applied" automation in FluentCRM can start emails from them:

| Stripe event | Tags added | Tags taken off |
|---|---|---|
| `checkout.session.completed` (paid) | Customer, the plan | Trial, Trial Ending, Cancelled, Checkout Abandoned |
| `invoice.paid` | Customer | Cancelled, Payment Failed, Past Due |
| `invoice.payment_failed` | Payment Failed | |
| `customer.subscription.updated` (plan or status changed) | The new plan; Past Due when late | The old plan; Past Due and Payment Failed once active again |
| `customer.subscription.trial_will_end` | Trial Ending | |
| `customer.subscription.deleted` | Cancelled | Customer, the plan, Past Due |
| `charge.refunded` (in full) | Refunded | Customer |
| `charge.dispute.created` | Disputed | |

1. In n8n, open **Workflows**, choose **Import from File** and pick the file.
2. In **Stripe Trigger**, **Get the customer** and **Get the charge**, choose your Stripe credential. A restricted key works if it can read customers and charges and write webhook endpoints. On activation, n8n adds the webhook to Stripe by itself and checks Stripe's signature on every event.
3. In FluentCRM, go to **Settings** > **Incoming Webhooks** and make a webhook. Leave its tags and lists empty: FluentCRM adds a webhook's own tags to any update that brings none, which would undo what this workflow leaves alone. In **Shape the update**, paste its address into `WEBHOOK_URL`. The address is the webhook's key, so keep it in n8n only. It must start with `https://`. Rename any tag in `TAGS` to match the tags you already use.
4. Set `metadata.plan` (for example `Pro`) on your Stripe Checkout Sessions and subscriptions, and the plan's name becomes a tag.
5. Activate the workflow.

Subscription events and disputes carry no email, so the workflow looks up the customer or the charge in Stripe first. A Stripe customer who was deleted has no email any more, and that run fails with a plain reason. Stripe doesn't promise to send events in order, so the workflow keeps when each contact's tags last changed, and a late event leaves alone any tag a newer one already changed (its other tags still change). Without this, a late "paid" could undo a cancel. Two limits: events created in the same second can't be told apart, so both apply in the order they arrive; and n8n saves the workflow's stored times as a whole when a run finishes, so runs that overlap can cross or lose each other's times. For a busy app, keep the times in your own database with the [TypeScript SDK](../../sdk/typescript/README.md#back-office-payments-from-your-own-app) instead. A run also fails when FluentCRM doesn't take an update, for example when the address is wrong. Failed runs show in **Executions**, and in your error workflow if you have one.

n8n's Stripe Trigger can't pick `checkout.session.async_payment_succeeded` (a bank debit that clears days later) or `checkout.session.expired` (a checkout left unfinished, tagged Checkout Abandoned). To use them, pick `*` (every event) in the trigger's events. The workflow handles both and ignores every other event. If your app has its own server, the [TypeScript SDK](../../sdk/typescript/README.md#back-office-payments-from-your-own-app) does the same job in code.

## Help

Step-by-step guides are in the [AnyPress docs](https://shop.anypress.io/docs-category/automation/), and recipes are on [anypress.io/automate](https://anypress.io/automate). Questions: support@anypress.io.

## License

MIT
