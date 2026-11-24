<?php
/**
 * The Swipe demo's past orders: about four in ten of the shop's shipped orders were protected at checkout, with three
 * claims (a lost parcel reshipped, a damaged one refunded, one still being looked at). Runs after the shop sample.
 */
require '/wordpress/wp-load.php';
wp_set_current_user(1);

use FluentCart\App\Models\Order;

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
