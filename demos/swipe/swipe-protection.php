<?php
/**
 * Plugin Name: Swipe Package Protection for FluentCart (concept)
 * Description: A concept preview for a conversation with Swipe AI: what Swipe's package protection could look like on
 *              a FluentCart store. Shoppers tick protection at checkout (a FluentCart fee), the store owner sees protected
 *              orders, claims and their share under FluentCart > Package Protection. Demo only: nothing is sent to Swipe.
 * Version: 0.1.0
 * License: GPL-3.0-or-later
 */

defined('ABSPATH') || exit;

// WordPress Playground runs on SQLite, which has no GET_LOCK(): FluentCart's checkout rate limiter then reads every
// order as "too many requests". In the browser demo only, the lock always succeeds so a visitor can place an order.
if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite') {
    add_filter('query', static fn ($q) => preg_match('/^\s*SELECT\s+(GET|RELEASE)_LOCK\(/i', (string) $q) ? "SELECT '1'" : $q);
}

const ANYPRESS_SWIPE_OPTION = 'anypress_swipe_settings';
const ANYPRESS_SWIPE_KEY = 'swipe_protection';

/** Swipe AI's own wordmark (white, from swipe.ai), on Swipe's dark chip. */
function anypress_swipe_logo(int $height = 18): string
{
    return '<img class="anypress-swipe-logo" src="data:image/png;base64,__SWIPE_WORDMARK__" alt="Swipe" style="height:' . (int) $height . 'px;width:auto;filter:invert(1);vertical-align:middle">';
}

/** FluentCart's own one-click checkout for the first thing in the shop that ships, so the demo opens straight on it. */
function anypress_swipe_checkout_url(): string
{
    $v = class_exists('\FluentCart\App\Models\ProductVariation') ? \FluentCart\App\Models\ProductVariation::query()
        ->where('fulfillment_type', 'physical')->where('stock_status', 'in-stock')->where('item_price', '>', 2000)->orderBy('id')->first() : null;
    return $v ? add_query_arg(['fluent-cart' => 'instant_checkout', 'item_id' => (int) $v->id, 'quantity' => 1], home_url('/')) : home_url('/shop/');
}

/** The store's protection settings, with Swipe's own figures (about 3% of the cart, half the premium to the store). */
function anypress_swipe_settings(): array
{
    return wp_parse_args((array) get_option(ANYPRESS_SWIPE_OPTION, []), [
        'enabled' => 'yes',
        'rate' => 3.0,
        'minimum' => 0.99,
        'share' => 50,
        'preticked' => 'no',
        'label' => 'Swipe Package Protection',
        'pitch' => 'Secure Your Package with Swipe. No More Package Theft.',
    ]);
}

/** What protection costs for a cart subtotal in cents. */
function anypress_swipe_price(int $subtotal): int
{
    $s = anypress_swipe_settings();
    return (int) max(round($s['minimum'] * 100), round($subtotal * $s['rate'] / 100));
}

/** Whether this cart has protection on: the shopper's own choice, else the store's default. */
function anypress_swipe_is_on($cart): bool
{
    $s = anypress_swipe_settings();
    if ($s['enabled'] !== 'yes' || !$cart || !$cart->requireShipping()) {
        return false;
    }
    $choice = $cart->checkout_data['anypress_swipe'] ?? null;
    return $choice ? $choice === 'yes' : $s['preticked'] === 'yes';
}

function anypress_swipe_money(int $cents): string
{
    return class_exists('\FluentCart\App\Helpers\Helper')
        ? html_entity_decode(wp_strip_all_tags((string) \FluentCart\App\Helpers\Helper::toDecimal($cents)))
        : '$' . number_format($cents / 100, 2);
}

/** The protection line itself, through FluentCart's own fees filter, so totals, tax, payment and receipts include it. */
add_filter('fluent_cart/cart/fees', static function ($fees, $args) {
    $cart = $args['cart'] ?? null;
    if (!anypress_swipe_is_on($cart)) {
        return $fees;
    }
    $fees[] = [
        'key' => ANYPRESS_SWIPE_KEY,
        'label' => anypress_swipe_settings()['label'],
        'amount' => anypress_swipe_price((int) ($args['cart_subtotal'] ?? 0)),
        'source' => 'swipe',
        'taxable' => false,
        'meta' => ['rate' => anypress_swipe_settings()['rate']],
    ];
    return $fees;
}, 10, 2);

/** The shopper's tick box, above the order summary on the checkout page. */
add_action('fluent_cart/before_order_summary', static function ($data) {
    $cart = $data['cart'] ?? null;
    $s = anypress_swipe_settings();
    if ($s['enabled'] !== 'yes' || !$cart || !$cart->requireShipping()) {
        return;
    }
    $on = anypress_swipe_is_on($cart);
    $price = anypress_swipe_price((int) $cart->getItemsSubtotal());
    ?>
    <div class="anypress-swipe" data-anypress-swipe>
        <label class="anypress-swipe__row">
            <span class="anypress-swipe__mark"><?php echo anypress_swipe_logo(16); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?></span>
            <span class="anypress-swipe__text">
                <strong>Package Protection <span class="anypress-swipe__price"><?php echo esc_html(anypress_swipe_money($price)); ?></span></strong>
                <span><?php echo esc_html($s['pitch']); ?></span>
            </span>
            <input type="checkbox" role="switch" class="anypress-swipe__box" aria-label="<?php echo esc_attr($s['label']); ?>" <?php checked($on); ?>
                data-nonce="<?php echo esc_attr(wp_create_nonce('anypress_swipe')); ?>"
                data-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        </label>
    </div>
    <?php
});

/** Saves the shopper's choice on their FluentCart cart; the checkout then reloads its summary as FluentCart's own order bumps do. */
$anypress_swipe_toggle = static function () {
    check_ajax_referer('anypress_swipe', 'nonce');
    $cart = \FluentCart\App\Helpers\CartHelper::getCart();
    if (!$cart) {
        wp_send_json_error(['message' => 'No cart'], 404);
    }
    $data = (array) ($cart->checkout_data ?: []);
    $data['anypress_swipe'] = sanitize_key($_POST['on'] ?? '') === 'yes' ? 'yes' : 'no';
    $cart->checkout_data = $data;
    $cart->save();
    wp_send_json_success(['on' => $data['anypress_swipe']]);
};
add_action('wp_ajax_anypress_swipe', $anypress_swipe_toggle);
add_action('wp_ajax_nopriv_anypress_swipe', $anypress_swipe_toggle);

add_action('wp_footer', static function () {
    if (!function_exists('is_page') || !did_action('fluent_cart/before_order_summary')) {
        return;
    }
    ?>
    <style>
        .anypress-swipe{border:1px solid #e4e1ec;border-radius:12px;padding:14px 16px;margin:0 0 16px;background:#fff;color:#111}
        .anypress-swipe__row{display:flex;gap:14px;align-items:center;cursor:pointer;margin:0}
        .anypress-swipe__mark{flex:none;line-height:0}
        .anypress-swipe__text{display:flex;flex-direction:column;gap:2px;font-size:.9em;line-height:1.35;flex:1;min-width:0}
        .anypress-swipe__text strong{display:flex;justify-content:space-between;gap:8px;font-size:1.05em}
        .anypress-swipe__text>span{color:#555}
        .anypress-swipe__price{font-variant-numeric:tabular-nums;white-space:nowrap;font-weight:500}
        .anypress-swipe .anypress-swipe__box{appearance:none!important;-webkit-appearance:none!important;flex:none;width:44px!important;height:24px!important;min-width:44px;border-radius:99px!important;background:#d4d2da!important;background-image:none!important;position:relative;cursor:pointer;margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;transition:background .2s}
        .anypress-swipe .anypress-swipe__box::before{display:none!important}
        .anypress-swipe .anypress-swipe__box::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.25);transition:transform .2s}
        .anypress-swipe .anypress-swipe__box:checked{background:#111!important}.anypress-swipe .anypress-swipe__box:checked::after{transform:translateX(20px)}
        .anypress-swipe__box:focus-visible{outline:2px solid #7b5cff;outline-offset:2px}
        @media (prefers-reduced-motion:reduce){.anypress-swipe__box,.anypress-swipe__box::after{transition:none}}
        .anypress-card{display:grid;gap:10px;padding:4px 0}.anypress-card label{display:grid;gap:4px;font-size:.85em;font-weight:500}
        .anypress-card input{width:100%;padding:10px 12px;border:1px solid #d6d3dc;border-radius:8px;font:inherit;font-size:1em;letter-spacing:.04em;background:#fff}
        .anypress-card__two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.anypress-card__note{margin:0;font-size:.8em;color:#666}
        .anypress-swipe.is-busy{opacity:.6;pointer-events:none}
    </style>
    <script>
    window.addEventListener('fluent_cart_load_payments_demo_card', function (e) {
        var b = window.fluentcart_checkout_vars && window.fluentcart_checkout_vars.submit_button;
        e.detail.paymentLoader.enableCheckoutButton(b ? b.text : 'Place Order');
    });
    document.addEventListener('change', function (e) {
        var box = e.target.closest && e.target.closest('.anypress-swipe__box');
        if (!box) return;
        var wrap = box.closest('[data-anypress-swipe]');
        wrap.classList.add('is-busy');
        var body = new URLSearchParams({ action: 'anypress_swipe', nonce: box.dataset.nonce, on: box.checked ? 'yes' : 'no' });
        var hash = new URLSearchParams(location.search).get('fct_cart_hash');
        if (hash) body.set('fct_cart_hash', hash);
        fetch(box.dataset.url, { method: 'POST', credentials: 'include', body: body })
            .then(function () { window.dispatchEvent(new Event('fluentCartNotifyCartDrawerItemChanged')); })
            .finally(function () { wrap.classList.remove('is-busy'); });
    });
    </script>
    <?php
});

/** Every protected order: the real ones placed at checkout (a "swipe" fee line) and the demo's past orders. */
function anypress_swipe_orders(): array
{
    if (!class_exists('\FluentCart\App\Models\Order')) {
        return [];
    }
    $rows = [];
    $orders = \FluentCart\App\Models\Order::query()->with(['customer', 'order_items'])->orderBy('id', 'desc')->limit(400)->get();
    foreach ($orders as $order) {
        $premium = 0;
        foreach ($order->order_items as $item) {
            if ($item->payment_type === 'fee' && (($item->other_info['source'] ?? '') === 'swipe')) {
                $premium += (int) $item->subtotal;
            }
        }
        $meta = $order->getMeta('_anypress_swipe', []);
        if (!$premium && empty($meta['premium'])) {
            continue;
        }
        $rows[] = [
            'order' => $order,
            'premium' => $premium ?: (int) $meta['premium'],
            'claim' => $meta['claim'] ?? null,
            'live' => $order->payment_status !== 'paid' || strtotime((string) $order->created_at) > time() - 2 * DAY_IN_SECONDS,
        ];
    }
    return $rows;
}

// A pretend card payment for the demo: a "Card" choice at checkout with a card form. Any card number goes through and
// the order is marked paid, the way a card order would be. Nothing is charged and nothing leaves the browser.
add_action('fluent_cart/register_payment_methods', static function ($args) {
    if (!class_exists('\FluentCart\App\Modules\PaymentMethods\Cod\Cod')) {
        return;
    }
    $card = new class () extends \FluentCart\App\Modules\PaymentMethods\Cod\Cod {
        public array $supportedFeatures = ['payment', 'refund', 'local_refund_only', 'multi_currency'];

        public function boot()
        {
        }

        public function meta(): array
        {
            return [
                'title' => 'Card',
                'route' => 'demo_card',
                'slug' => 'demo_card',
                'description' => 'Pay by card (demo: nothing is charged)',
                'logo' => \FluentCart\App\Vite::getAssetUrl('images/payment-methods/card.svg'),
                'icon' => \FluentCart\App\Vite::getAssetUrl('images/payment-methods/card.svg'),
                'brand_color' => '#111111',
                'upcoming' => false,
                'status' => true,
            ];
        }

        public function makePaymentFromPaymentInstance(\FluentCart\App\Services\Payments\PaymentInstance $paymentInstance)
        {
            $order = $paymentInstance->order;
            $order->payment_method_title = 'Card';
            $order->save();
            $transaction = $paymentInstance->transaction;
            $transaction->status = \FluentCart\App\Helpers\Status::TRANSACTION_SUCCEEDED;
            $transaction->card_brand = 'visa';
            $transaction->card_last_4 = '4242';
            $transaction->save();
            (new \FluentCart\App\Helpers\StatusHelper($order))->syncOrderStatuses($transaction);
            $cart = \FluentCart\App\Models\Cart::query()->where('order_id', $order->id)->where('stage', '!=', 'completed')->first();
            if ($cart) {
                $cart->stage = 'completed';
                $cart->completed_at = current_time('mysql');
                $cart->save();
            }
            return ['status' => 'success', 'message' => 'Payment received', 'redirect_to' => $transaction->getSuccessUrl()];
        }

        public function getEnqueueScriptSrc($hasSubscription = 'no'): array
        {
            return [];
        }
    };
    $args['gatewayManager']->register('demo_card', $card);
});

add_action('fluent_cart/checkout_embed_payment_method_content', static function ($data) {
    if (($data['route'] ?? '') !== 'demo_card') {
        return;
    }
    ?>
    <div class="anypress-card">
        <label>Card number<input type="text" inputmode="numeric" autocomplete="off" value="4242 4242 4242 4242"></label>
        <div class="anypress-card__two">
            <label>Expiry<input type="text" autocomplete="off" value="12 / 30"></label>
            <label>CVC<input type="text" inputmode="numeric" autocomplete="off" value="123"></label>
        </div>
        <p class="anypress-card__note">Demo checkout: any card goes through and nothing is charged.</p>
    </div>
    <?php
});

/** A shortcut in the toolbar on every page, so the demo moves between the shop and the owner's screen in one click. */
add_action('admin_bar_menu', static function ($bar) {
    if (current_user_can('manage_options')) {
        $bar->add_node(['id' => 'anypress-swipe', 'title' => 'Swipe Protection', 'href' => admin_url('admin.php?page=anypress-swipe')]);
        $bar->add_node(['id' => 'anypress-swipe-checkout', 'parent' => 'anypress-swipe', 'title' => 'Try the Checkout', 'href' => anypress_swipe_checkout_url()]);
    }
}, 80);

add_action('admin_menu', static function () {
    add_submenu_page('fluent-cart', 'Package Protection', 'Package Protection', 'manage_options', 'anypress-swipe', 'anypress_swipe_page', 30);
}, 99);

add_action('admin_post_anypress_swipe_settings', static function () {
    check_admin_referer('anypress_swipe_settings');
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed');
    }
    update_option(ANYPRESS_SWIPE_OPTION, [
        'enabled' => isset($_POST['enabled']) ? 'yes' : 'no',
        'preticked' => isset($_POST['preticked']) ? 'yes' : 'no',
        'rate' => min(20, max(0.5, (float) ($_POST['rate'] ?? 3))),
        'minimum' => min(10, max(0, (float) ($_POST['minimum'] ?? 0.99))),
        'share' => min(100, max(0, (int) ($_POST['share'] ?? 50))),
        'label' => sanitize_text_field(wp_unslash($_POST['label'] ?? '')) ?: 'Swipe Package Protection',
        'pitch' => sanitize_text_field(wp_unslash($_POST['pitch'] ?? '')),
    ]);
    wp_safe_redirect(admin_url('admin.php?page=anypress-swipe&saved=1'));
    exit;
});

function anypress_swipe_page(): void
{
    $s = anypress_swipe_settings();
    $rows = anypress_swipe_orders();
    $offered = class_exists('\FluentCart\App\Models\Order') ? \FluentCart\App\Models\Order::query()->where('created_at', '>=', gmdate('Y-m-d', strtotime('-200 days')))->count() : 0;
    $premiums = array_sum(array_column($rows, 'premium'));
    $share = (int) round($premiums * $s['share'] / 100);
    $claims = array_values(array_filter($rows, static fn ($r) => $r['claim']));
    $paidOut = array_sum(array_map(static fn ($r) => (int) ($r['claim']['value'] ?? 0), array_filter($claims, static fn ($r) => ($r['claim']['status'] ?? '') !== 'Open')));
    $month = array_filter($rows, static fn ($r) => strtotime((string) $r['order']->created_at) >= strtotime('first day of this month 00:00'));
    $monthShare = (int) round(array_sum(array_column($month, 'premium')) * $s['share'] / 100);
    $orderUrl = static fn ($id) => admin_url('admin.php?page=fluent-cart#/orders/' . (int) $id . '/view');
    ?>
    <div class="wrap ap-swipe">
        <style>
            .ap-swipe{--ink:#1d2327;--muted:#646970;--line:#dcdcde;--blue:#6d4cff;--soft:#f3effd;max-width:none;margin-right:20px}
            .ap-swipe h1{display:flex;align-items:center;gap:10px}
            .ap-swipe .pill{font-size:11px;font-weight:600;background:#fff4d6;color:#7a5600;border-radius:99px;padding:3px 10px}
            .ap-swipe .lede{color:var(--muted);max-width:760px;font-size:14px}
            .ap-swipe .grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:18px 0}
            @media (max-width:1400px){.ap-swipe .grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
            .ap-swipe .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px 16px}
            .ap-swipe .stat b{display:block;font-size:24px;line-height:1.2;color:var(--ink);font-variant-numeric:tabular-nums}
            .ap-swipe .stat span{color:var(--muted);font-size:12px}
            .ap-swipe .stat.hi{background:var(--soft);border-color:#d9cdfb}
            .ap-swipe .hero{background:linear-gradient(135deg,#fbe3ec,#e6dcfb);border-radius:14px;padding:22px 26px;margin:12px 0 18px}.ap-swipe .hero h1{margin:0 0 8px}.ap-swipe .hero .lede{color:#3c3846}
            .ap-swipe .button-primary{background:#111;border-color:#111}.ap-swipe .button-primary:hover,.ap-swipe .button-primary:focus{background:#333;border-color:#333}
            .ap-swipe .cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,26%);gap:16px;align-items:start}.ap-swipe .cols>*{min-width:0}
            @media (max-width:1000px){.ap-swipe .cols{grid-template-columns:minmax(0,1fr)}}
            .ap-swipe td:first-child,.ap-swipe .tag{white-space:nowrap}
            .ap-swipe .card{max-width:none;min-width:0;background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px 18px;margin-bottom:16px}
            .ap-swipe .card h2{margin:0 0 10px;font-size:15px}
            .ap-swipe table{width:100%;border-collapse:collapse;font-size:13px}
            .ap-swipe th{text-align:left;color:var(--muted);font-weight:500;border-bottom:1px solid var(--line);padding:6px 8px}
            .ap-swipe td{border-bottom:1px solid #f0f0f1;padding:8px}
            .ap-swipe td.num,.ap-swipe th.num{text-align:right;font-variant-numeric:tabular-nums}
            .ap-swipe .scroll{max-height:420px;overflow:auto}
            .ap-swipe .tag{display:inline-block;border-radius:99px;padding:2px 9px;font-size:11px;font-weight:600;background:#e7f6ec;color:#1a6b37}
            .ap-swipe .tag.open{background:#fff4d6;color:#7a5600}.ap-swipe .tag.claim{background:#eef1ff;color:#2a3fb0}.ap-swipe .tag.live{background:#6d4cff;color:#fff}
            .ap-swipe .flow{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:0;padding:0}
            @media (max-width:900px){.ap-swipe .flow{grid-template-columns:1fr 1fr}}
            .ap-swipe .flow li{list-style:none;background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin:0}
            .ap-swipe .flow b{display:block;margin-bottom:4px}.ap-swipe .flow span{color:var(--muted);font-size:12px}
            .ap-swipe label.f{display:block;margin:0 0 10px;font-weight:500}
            .ap-swipe label.f input[type=text],.ap-swipe label.f input[type=password],.ap-swipe label.f input[type=number]{width:100%;margin-top:4px}
            .ap-swipe .two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
            .ap-swipe .note{color:var(--muted);font-size:12px}
        </style>
        <div class="hero">
        <h1><?php echo anypress_swipe_logo(22); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?> Package Protection <span class="pill">Concept preview</span></h1>
        <p class="lede">Shoppers add Swipe's protection at checkout for about <?php echo esc_html((string) $s['rate']); ?>% of their cart. If a parcel is lost, stolen or arrives damaged, Swipe handles the claim and reships or refunds. The store keeps <?php echo esc_html((string) $s['share']); ?>% of what shoppers pay, on orders it already ships.</p>
        <p><a class="button button-primary button-hero" href="<?php echo esc_url(anypress_swipe_checkout_url()); ?>">Try the Checkout</a> <a class="button button-hero" href="<?php echo esc_url(home_url('/')); ?>">View the Store</a></p>
        </div>
        <?php if (isset($_GET['saved'])) : ?><div class="notice notice-success"><p>Settings saved. Try the checkout to see the change.</p></div><?php endif; ?>

        <ol class="flow">
            <li><b>1 · Shopper Ticks Protection</b><span>One line above the order summary at checkout</span></li>
            <li><b>2 · FluentCart Adds It</b><span>A fee line in the total, the payment and the receipt</span></li>
            <li><b>3 · Swipe Handles Claims</b><span>Reship or refund, no work for the store</span></li>
            <li><b>4 · Store Earns Its Share</b><span>Paid monthly on protected orders</span></li>
        </ol>

        <div class="grid">
            <div class="stat"><b><?php echo esc_html((string) count($rows)); ?></b><span>Protected orders (6 months)</span></div>
            <div class="stat"><b><?php echo esc_html($offered ? round(count($rows) / $offered * 100) . '%' : '–'); ?></b><span>Shoppers who added it</span></div>
            <div class="stat"><b><?php echo esc_html(anypress_swipe_money($premiums)); ?></b><span>Paid by shoppers</span></div>
            <div class="stat hi"><b><?php echo esc_html(anypress_swipe_money($share)); ?></b><span>Store's share (<?php echo esc_html((string) $s['share']); ?>%)</span></div>
            <div class="stat"><b><?php echo esc_html(anypress_swipe_money($monthShare)); ?></b><span>Share so far this month</span></div>
            <div class="stat"><b><?php echo esc_html(count($claims) . ' · ' . anypress_swipe_money($paidOut)); ?></b><span>Claims · covered by Swipe</span></div>
        </div>

        <div class="cols">
            <div>
                <div class="card">
                    <h2>Protected Orders</h2>
                    <div class="scroll">
                        <table>
                            <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th class="num">Total</th><th class="num">Protection</th><th class="num">Store's share</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($rows as $r) :
                                $o = $r['order'];
                                $claim = $r['claim'];
                                $status = $claim ? ($claim['status'] === 'Open' ? 'Claim open' : 'Claim ' . strtolower($claim['status'])) : ($r['live'] ? 'New' : 'Protected');
                                $cls = $claim ? ($claim['status'] === 'Open' ? 'open' : 'claim') : ($r['live'] ? 'live' : '');
                                ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($orderUrl($o->id)); ?>">#<?php echo esc_html((string) ($o->invoice_no ?: $o->id)); ?></a></td>
                                    <td><?php echo esc_html($o->customer ? trim($o->customer->first_name . ' ' . $o->customer->last_name) : 'Guest'); ?></td>
                                    <td><?php echo esc_html(mysql2date('M j', (string) $o->created_at)); ?></td>
                                    <td class="num"><?php echo esc_html(anypress_swipe_money((int) $o->total_amount)); ?></td>
                                    <td class="num"><?php echo esc_html(anypress_swipe_money($r['premium'])); ?></td>
                                    <td class="num"><?php echo esc_html(anypress_swipe_money((int) round($r['premium'] * $s['share'] / 100))); ?></td>
                                    <td><span class="tag <?php echo esc_attr($cls); ?>"><?php echo esc_html($status); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows) : ?><tr><td colspan="7">No protected orders yet. Place an order on the shop and tick protection at checkout.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <h2>Claims</h2>
                    <table>
                        <thead><tr><th>Order</th><th>Customer</th><th>What happened</th><th>Filed</th><th>Outcome</th><th class="num">Covered</th></tr></thead>
                        <tbody>
                        <?php foreach ($claims as $r) : $c = $r['claim']; ?>
                            <tr>
                                <td><a href="<?php echo esc_url($orderUrl($r['order']->id)); ?>">#<?php echo esc_html((string) ($r['order']->invoice_no ?: $r['order']->id)); ?></a></td>
                                <td><?php echo esc_html($r['order']->customer ? trim($r['order']->customer->first_name . ' ' . $r['order']->customer->last_name) : 'Guest'); ?></td>
                                <td><?php echo esc_html($c['reason']); ?></td>
                                <td><?php echo esc_html(mysql2date('M j', $c['filed'])); ?></td>
                                <td><span class="tag <?php echo esc_attr($c['status'] === 'Open' ? 'open' : 'claim'); ?>"><?php echo esc_html($c['status']); ?></span></td>
                                <td class="num"><?php echo esc_html(anypress_swipe_money((int) $c['value'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$claims) : ?><tr><td colspan="6">No claims.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                    <p class="note">Claims go to Swipe's own team. The store sees each one here and on the order, and never pays for a reship or refund.</p>
                </div>
            </div>
            <div>
                <form class="card" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <h2>Settings</h2>
                    <?php wp_nonce_field('anypress_swipe_settings'); ?>
                    <input type="hidden" name="action" value="anypress_swipe_settings">
                    <label class="f"><input type="checkbox" name="enabled" <?php checked($s['enabled'], 'yes'); ?>> Offer protection at checkout</label>
                    <label class="f"><input type="checkbox" name="preticked" <?php checked($s['preticked'], 'yes'); ?>> Tick it for the shopper (they can untick)</label>
                    <div class="two">
                        <label class="f">Price, % of cart<input type="number" step="0.1" name="rate" value="<?php echo esc_attr((string) $s['rate']); ?>"></label>
                        <label class="f">Lowest price, $<input type="number" step="0.01" name="minimum" value="<?php echo esc_attr((string) $s['minimum']); ?>"></label>
                    </div>
                    <label class="f">Store's share, %<input type="number" name="share" value="<?php echo esc_attr((string) $s['share']); ?>"></label>
                    <label class="f">Line at checkout<input type="text" name="label" value="<?php echo esc_attr($s['label']); ?>"></label>
                    <label class="f">What it covers<input type="text" name="pitch" value="<?php echo esc_attr($s['pitch']); ?>"></label>
                    <?php submit_button('Save', 'primary', 'submit', false); ?>
                    <p class="note">Only orders with something to ship get the line. Digital products, gift cards and bookings never do.</p>
                </form>
                <div class="card">
                    <h2>Try It</h2>
                    <p>Opens the checkout with a piece from the shop in the cart. Tick protection above the order summary. The total changes at once, the order and receipt show the line, and the order appears here.</p>
                    <p><a class="button button-primary" href="<?php echo esc_url(anypress_swipe_checkout_url()); ?>">Try the Checkout</a> <a class="button" href="<?php echo esc_url(home_url('/shop/')); ?>">Open the Shop</a></p>
                    <p class="note">Concept preview made for a conversation with Swipe AI. The figures on past orders are sample data; no order is sent to Swipe.</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
