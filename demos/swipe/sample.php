<?php
/**
 * The Swipe demo's history on the Aisle kit's shop (Thread & Co.): six months of orders from customers across the US,
 * placed as FluentCart's admin screen places them and paid, shipped or delivered on the day it happened. About four in
 * ten shoppers added Swipe's protection at checkout (a real "Swipe Package Protection" fee line on the order), and three
 * of those parcels had a claim (a lost parcel reshipped, a damaged one refunded, one still being looked at).
 */
require '/wordpress/wp-load.php';
wp_set_current_user(1);
add_filter('pre_wp_mail', '__return_false', 99); // nothing is mailed from the demo

use FluentCart\Api\Resource\CustomerResource;
use FluentCart\Api\Resource\OrderResource;
use FluentCart\App\Models\Customer;
use FluentCart\App\Models\Order;
use FluentCart\App\Models\OrderItem;
use FluentCart\App\Models\OrderTransaction;
use FluentCart\App\Models\ProductVariation;

$api = static function (string $method, string $route, array $body = []) {
    $r = new WP_REST_Request($method, '/fluent-cart/v2' . $route);
    $r->set_header('content-type', 'application/json');
    $r->set_body((string) wp_json_encode($body));
    return rest_do_request($r)->get_data();
};

$people = [
    ['Sofia', 'Ramirez', 'Austin', 'TX'], ['Ryan', 'Foster', 'Denver', 'CO'], ['Zoe', 'Thompson', 'Seattle', 'WA'],
    ['Grace', 'Kim', 'Portland', 'OR'], ['Ethan', 'Parker', 'Chicago', 'IL'], ['Aisha', 'Khan', 'Atlanta', 'GA'],
    ['Hannah', 'Schmidt', 'Minneapolis', 'MN'], ['Marcus', 'Bell', 'Nashville', 'TN'], ['Priya', 'Nair', 'San Jose', 'CA'],
    ['Diego', 'Morales', 'Phoenix', 'AZ'], ['Leah', 'Goldberg', 'Boston', 'MA'], ['Omar', 'Haddad', 'Detroit', 'MI'],
    ['Chloe', 'Martin', 'Miami', 'FL'], ['Jamal', 'Washington', 'Raleigh', 'NC'], ['Emma', 'Lindqvist', 'Columbus', 'OH'],
    ['Noah', 'Okafor', 'Houston', 'TX'], ['Mia', 'Rossi', 'Philadelphia', 'PA'], ['Lucas', 'Silva', 'San Diego', 'CA'],
];

$customer = static function (array $p, int $n) use ($api): ?Customer {
    $email = strtolower($p[0] . '.' . $p[1]) . '@anypress.example';
    if (!$c = Customer::where('email', $email)->first()) {
        CustomerResource::create(['email' => $email, 'full_name' => "$p[0] $p[1]", 'first_name' => $p[0], 'last_name' => $p[1]]);
        $c = Customer::where('email', $email)->first();
        foreach (['billing', 'shipping'] as $kind) {
            $api('POST', "/customers/{$c->id}/address", [
                'type' => $kind, 'is_primary' => 1, 'label' => 'Home', 'name' => "$p[0] $p[1]", 'email' => $email,
                'address_1' => (120 + $n * 17) . ' Maple Avenue', 'city' => $p[2], 'state' => $p[3], 'postcode' => (string) (10200 + $n * 911), 'country' => 'US',
            ]);
        }
    }
    return $c;
};

$variations = ProductVariation::query()->where('fulfillment_type', 'physical')->where('item_price', '>', 0)->orderBy('id')->get()->all();
if (!$variations) {
    return;
}

$claims = [
    3 => ['Lost in transit: tracking stopped at the carrier depot', 'Reshipped', 6],
    8 => ['Arrived damaged: box crushed, sleeve torn', 'Refunded', 4],
    12 => ['Marked delivered, never received', 'Open', 3],
];
$protectedCount = 0;
$total = 34;
for ($i = 0; $i < $total; $i++) {
    $daysAgo = round(178 - $i * 5.2 + ($i % 3), 1);
    $p = $people[($i * 7) % count($people)];
    $c = $customer($p, ($i * 7) % count($people));
    if (!$c) {
        continue;
    }
    $lines = [[$variations[($i * 5) % count($variations)], 1 + ($i % 4 === 0 ? 1 : 0)]];
    if ($i % 3 === 1) {
        $lines[] = [$variations[($i * 11 + 3) % count($variations)], 1];
    }
    $items = [];
    $subtotal = 0;
    foreach ($lines as [$v, $q]) {
        $subtotal += (int) $v->item_price * $q;
        $items[] = [
            'object_id' => $v->id, 'post_id' => $v->post_id, 'quantity' => $q, 'unit_price' => $v->item_price,
            'title' => $v->variation_title, 'post_title' => get_the_title($v->post_id), 'fulfillment_type' => 'physical',
            'other_info' => $v->other_info, 'payment_type' => 'onetime',
        ];
    }
    $order = OrderResource::updatedPlaceOrder(['customer_id' => $c->id, 'order_items' => $items]);
    if (!$order instanceof Order) {
        continue;
    }
    // Two in every five shoppers ticked Swipe's protection: the fee line as checkout writes it, and the order's totals.
    $protected = in_array($i % 5, [1, 3], true);
    if ($protected) {
        $premium = (int) max(99, round($subtotal * 0.03));
        OrderItem::create([
            'order_id' => $order->id, 'payment_type' => 'fee', 'post_id' => 0, 'object_id' => 0, 'post_title' => '', 'title' => 'Swipe Package Protection',
            'quantity' => 1, 'unit_price' => $premium, 'subtotal' => $premium, 'line_total' => $premium, 'fulfillment_type' => 'digital',
            'other_info' => ['payment_type' => 'fee', 'fee_key' => 'swipe_protection', 'source' => 'swipe', 'taxable' => false, 'meta' => ['rate' => 3]],
        ]);
        $o = Order::find($order->id);
        $o->fee_total = (int) $o->fee_total + $premium;
        $o->total_amount = (int) $o->total_amount + $premium;
        $o->save();
        $protectedCount++;
    }
    $last = $i >= $total - 2;
    if (!$last) {
        $api('POST', "/orders/{$order->id}/mark-as-paid", ['payment_method' => 'offline_payment']);
        $api('PUT', "/orders/{$order->id}/statuses", ['action' => 'change_shipping_status', 'statuses' => ['shipping_status' => 'shipped']]);
        if ($daysAgo > 10) {
            $api('PUT', "/orders/{$order->id}/statuses", ['action' => 'change_order_status', 'statuses' => ['order_status' => 'completed']]);
            $o = Order::find($order->id);
            $o->shipping_status = 'delivered';
            $o->save();
        }
    }
    $when = gmdate('Y-m-d H:i:s', (int) (strtotime('today 17:00 UTC') - $daysAgo * DAY_IN_SECONDS + ($i % 6) * 3600));
    foreach (array_merge([Order::find($order->id)], OrderTransaction::where('order_id', $order->id)->get()->all()) as $row) {
        if ($row) {
            $row->created_at = $when;
            $row->updated_at = $when;
            $row->save();
        }
    }
    if ($protected && isset($claims[$protectedCount])) {
        [$reason, $status, $after] = $claims[$protectedCount];
        Order::find($order->id)->updateMeta('_anypress_swipe', ['claim' => [
            'reason' => $reason, 'status' => $status, 'value' => $subtotal,
            'filed' => gmdate('Y-m-d H:i:s', strtotime($when) + $after * DAY_IN_SECONDS),
        ]]);
    }
}
foreach (Customer::get() as $c) {
    $c->recountStat();
}
if (function_exists('as_unschedule_all_actions')) {
    as_unschedule_all_actions('fluent_cart/order_paid_async_private_handle', [], 'fluent-cart');
}
