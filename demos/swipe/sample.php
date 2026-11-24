<?php
/**
 * The Swipe demo's past orders: about four in ten of the shop's shipped orders were protected at checkout, with three
 * claims (a lost parcel reshipped, a damaged one refunded, one still being looked at). Runs after the shop sample.
 */
require '/wordpress/wp-load.php';
wp_set_current_user(1);

use FluentCart\App\Models\Order;
use FluentCart\App\Modules\PaymentMethods\Core\GatewayManager;

// So a visitor can place a real order in the demo: FluentCart's own Cash on Delivery, switched on as its Payments
// screen does. Nothing is charged.
if (GatewayManager::has('offline_payment')) {
    GatewayManager::getInstance('offline_payment')->updateSettings(['is_active' => 'yes']);
}

// Shipping, so the checkout offers delivery (the shop part's own setup doesn't always finish in the browser): the
// same zone and methods, through FluentCart's own admin routes, when there is none yet.
$api = static function (string $route, array $body) {
    $r = new WP_REST_Request('POST', $route);
    $r->set_header('content-type', 'application/json');
    $r->set_body((string) wp_json_encode($body));
    return rest_do_request($r)->get_data();
};
if (!\FluentCart\App\Models\ShippingZone::query()->count()) {
    $zoneId = (int) ($api('/fluent-cart/v2/shipping/zones', ['name' => 'United States', 'region' => 'US'])['shipping_zone']['id'] ?? 0);
    foreach ($zoneId ? [['Standard (3 to 5 days)', 'flat_rate', '8'], ['Express (1 to 2 days)', 'flat_rate', '18'], ['Pick up from the studio', 'local_pickup', '0']] : [] as [$title, $kind, $amount]) {
        $api('/fluent-cart/v2/shipping/methods', ['zone_id' => $zoneId, 'title' => $title, 'type' => $kind, 'amount' => $amount, 'settings' => ['configure_rate' => 'per_order'], 'is_enabled' => 1]);
    }
}

$orders = Order::query()->with('order_items')->whereIn('payment_status', ['paid', 'partially_refunded'])->orderBy('id')->get();
$n = 0;
$claims = [
    ['Lost in transit: tracking stopped at the carrier depot', 'Reshipped', 2],
    ['Arrived damaged: box crushed, item torn', 'Refunded', 4],
    ['Marked delivered, never received', 'Open', 1],
];
$protected = [];
foreach ($orders as $order) {
    $subtotal = 0;
    foreach ($order->order_items as $item) {
        if (($item->fulfillment_type ?? '') === 'physical' && $item->payment_type !== 'fee') {
            $subtotal += (int) $item->subtotal;
        }
    }
    if (!$subtotal || (++$n % 5) > 1) { // two in every five shipped orders
        continue;
    }
    $protected[] = [$order, (int) max(99, round($subtotal * 0.03)), $subtotal];
}
// Claims on recent orders, newest last.
$recent = array_slice($protected, -9);
foreach ($claims as $i => [$reason, $status, $daysAfter]) {
    if (!isset($recent[$i * 3])) {
        break;
    }
    [$order, , $subtotal] = $recent[$i * 3];
    $recent[$i * 3][3] = [
        'reason' => $reason,
        'status' => $status,
        'filed' => gmdate('Y-m-d H:i:s', strtotime((string) $order->created_at) + ($daysAfter + 5) * DAY_IN_SECONDS),
        'value' => $subtotal,
    ];
}
foreach (array_merge(array_slice($protected, 0, -9), $recent) as $row) {
    $row[0]->updateMeta('_anypress_swipe', ['premium' => $row[1], 'claim' => $row[3] ?? null]);
}
