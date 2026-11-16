<?php
/**
 * One form that books the right person (an industry demo's 'routing'): Fluent Forms asks what the client needs, shows
 * only the questions for that, and a FluentBooking field for each service books it on that host's calendar (shown by
 * Conditional Logic). With FluentCRM on, the form's FluentCRM feed tags each new contact by what they need.
 *
 * The form is built from Fluent Forms' own default elements and FluentBooking's booking field, as its editor saves
 * them. Its entries are linked to their bookings both ways, as FluentBooking links them when the form is submitted:
 * the booking shows the answers and the entry shows the booking.
 */
use FluentBooking\App\Models\Booking;
use FluentBooking\App\Models\CalendarSlot;
use FluentBooking\App\Services\BookingService;

$plan = $sample['routing'];
$people = $sample['people'];
$defaults = fluentformLoadFile('Services/FormBuilder/DefaultElements.php');
$n = 0;
/** One of Fluent Forms' default elements, with this form's settings over it. */
$field = static function (string $group, string $element, array $over) use ($defaults, &$n): array {
    $made = array_replace_recursive($defaults[$group][$element], $over);
    // Lists are the form's own, not merged into the defaults'.
    foreach (['advanced_options'] as $key) {
        if (isset($over['settings'][$key])) {
            $made['settings'][$key] = $over['settings'][$key];
        }
    }
    unset($made['editor_options']['template_options']);
    $made['uniqElKey'] = 'el_' . (time() + ++$n) . '0.' . $n;
    return $made;
};
$options = static fn (array $list) => array_map(static fn ($o) => ['label' => $o, 'value' => $o, 'calc_value' => '', 'image' => ''], $list);
$when = static fn (string $area) => ['type' => 'any', 'status' => true, 'conditions' => [['field' => 'need', 'value' => $area, 'operator' => '=']]];
$required = static fn (bool $on = true) => ['required' => ['value' => $on, 'message' => __('This field is required'), 'global' => true]];

[$question, $areas] = $plan['need'];
$fields = [
    $field('general', 'input_name', ['settings' => ['admin_field_label' => 'Name'], 'fields' => [
        'first_name' => ['settings' => ['label' => __('First name'), 'validation_rules' => $required()]],
        'last_name' => ['settings' => ['visible' => true, 'label' => __('Last name'), 'validation_rules' => $required()]],
    ]]),
    $field('general', 'input_email', ['settings' => ['label' => __('Email'), 'validation_rules' => ['required' => ['value' => true]]]]),
    // The free plugin's phone field: a text field (the Phone field is Fluent Forms Pro).
    $field('general', 'input_text', ['attributes' => ['name' => 'phone'], 'settings' => ['label' => __('Phone number'), 'admin_field_label' => __('Phone number')]]),
    $field('general', 'input_radio', [
        'attributes' => ['name' => 'need'],
        'settings' => ['label' => $question, 'admin_field_label' => $question, 'advanced_options' => $options(array_column($areas, 0)), 'validation_rules' => $required()],
    ]),
];
// Each area's own questions, shown only for it.
foreach ($areas as [$area, , $asks]) {
    foreach ($asks as [$name, $label, $type, $choices]) {
        $element = ['text' => 'input_text', 'choice' => 'input_radio', 'list' => 'select'][$type];
        $fields[] = $field('general', $element, [
            'attributes' => ['name' => $name],
            'settings' => ['label' => $label, 'admin_field_label' => $label, 'conditional_logics' => $when($area)] + ($choices ? ['advanced_options' => $options($choices)] : []),
        ]);
    }
}
foreach ([['parties', $plan['parties']], ['about', $plan['about']]] as [$name, [$label, $help]]) {
    $fields[] = $field('general', 'textarea', ['attributes' => ['name' => $name, 'rows' => 3], 'settings' => ['label' => $label, 'admin_field_label' => $label, 'help_message' => $help]]);
}
// A FluentBooking field for each area, on its host's calendar, last: clients answer first, then pick a time.
$events = [];
foreach ($areas as $a => [$area, $event]) {
    $title = $sample['bookings']['events'][$event][1] ?? '';
    $slot = $title ? CalendarSlot::where('title', $title)->first() : null;
    if (!$slot) {
        continue;
    }
    $events[$a] = $slot;
    $fields[] = [
        'index' => 20,
        'element' => 'fcal_booking',
        'attributes' => ['name' => 'booking_' . $a, 'data-type' => 'fcal_booking'],
        'settings' => [
            'label' => $plan['pick'],
            'admin_field_label' => $title,
            'label_placement' => '',
            'event_id' => (string) $slot->id,
            'booking_calendar' => (string) $slot->calendar_id,
            'conditional_logics' => $when($area),
            'container_class' => '',
            'cal_guest_fields' => ['email_field' => 'email', 'name_field' => 'names', 'host_info' => 'show'],
            'validation_rules' => ['required' => ['value' => true, 'message' => __('Pick a time for your consultation')]],
        ],
        'editor_options' => ['title' => 'FluentBooking Field', 'icon_class' => 'el-icon-date', 'template' => 'inputCalendar'],
        'uniqElKey' => 'el_' . (time() + ++$n) . '0.' . $n,
    ];
}
$fields[] = $field('advanced', 'terms_and_condition', ['settings' => ['tnc_html' => esc_html($plan['terms']), 'admin_field_label' => __('Disclaimer'), 'validation_rules' => $required()]]);

$form = (new \FluentForm\App\Services\Form\FormService())->store(['predefined' => 'blank_form', 'type' => 'form']);
$form->title = $plan['title'];
$saved = json_decode($form->form_fields, true);
$saved['fields'] = $fields;
$saved['submitButton']['settings']['button_ui']['text'] = $plan['button'];
$form->form_fields = wp_json_encode($saved);
$form->save();

// FluentCRM's feed on the form: every client becomes a contact, tagged by what they need ("Family Law", ...). No list:
// the form asks no newsletter consent.
$tags = [];
if (defined('FLUENTCRM')) {
    foreach ($areas as [$area]) {
        $tag = \FluentCrm\App\Models\Tag::where('title', $area)->first() ?: \FluentCrm\App\Models\Tag::create(['title' => $area, 'slug' => sanitize_title($area)]);
        $tags[$area] = (int) $tag->id;
    }
    \FluentForm\App\Models\FormMeta::create(['form_id' => $form->id, 'meta_key' => 'fluentcrm_feeds', 'value' => wp_json_encode([
        'name' => __('Tag new clients by what they need'),
        'full_name' => '{inputs.names}',
        'email' => '{inputs.email}',
        'other_fields' => [['item_value' => '{inputs.phone}', 'label' => 'phone']],
        'company_fields' => [['item_value' => '', 'label' => '']],
        'list_id' => '',
        'tag_ids' => [],
        'tag_ids_selection_type' => 'routers',
        'tag_routers' => array_map(static fn ($area, $id) => ['field' => 'need', 'operator' => '=', 'value' => $area, 'input_value' => (string) $id], array_keys($tags), $tags),
        'skip_if_exists' => false,
        'double_opt_in' => false,
        'force_subscribe' => false,
        'skip_primary_data' => false,
        'conditionals' => ['conditions' => [], 'status' => false, 'type' => 'all'],
        'run_events_only' => [],
        'remove_tags' => [],
        'enabled' => true,
    ])]);
}

// Clients who booked through the form: [person, area, days ahead (negative: past), hour UTC, answers, other parties,
// what is happening, status]. Each entry is saved a few days before its consultation, then its booking.
foreach ($plan['entries'] as $i => [$who, $a, $daysAhead, $hour, $answers, $parties, $about, $status]) {
    $slot = $events[$a] ?? null;
    if (!$slot) {
        continue;
    }
    $p = $people[$who];
    $start = strtotime('today ' . $hour . ':00 UTC') + $daysAhead * DAY_IN_SECONDS;
    while (in_array(gmdate('w', $start), ['0', '6'], true)) {
        $start += DAY_IN_SECONDS;
    }
    $zone = 'America/New_York';
    $local = (new DateTime('@' . $start))->setTimezone(new DateTimeZone($zone))->format('Y-m-d H:i:s');
    $field = 'booking_' . $a;
    $data = [
        'names' => ['first_name' => $p[0], 'last_name' => $p[1]],
        'email' => anypress_sample_email($p),
        'phone' => '(614) 555-01' . str_pad((string) (20 + $i), 2, '0', STR_PAD_LEFT),
        'need' => $areas[$a][0],
    ] + $answers + [
        'parties' => $parties,
        'about' => $about,
        $field => ['start_time' => $local, 'timezone' => $zone, 'duration' => (int) $slot->duration],
        'terms-n-condition' => 'on',
    ];
    $booked = min(time() - (2 + $i % 5) * HOUR_IN_SECONDS, $start - (2 + $i % 4) * DAY_IN_SECONDS);
    $entryId = anypress_sample_entry($form, $data, (time() - $booked) / DAY_IN_SECONDS, $i < 3 ? 'unread' : 'read', $i === 0, $plan['page']);
    try {
        $booking = BookingService::createBooking([
            'first_name' => $p[0],
            'last_name' => $p[1],
            'email' => anypress_sample_email($p),
            'phone' => $data['phone'],
            'message' => $about,
            'start_time' => gmdate('Y-m-d H:i:s', $start),
            'person_time_zone' => $zone,
            'status' => 'scheduled',
            'source' => 'fluentform',
            'source_id' => $entryId,
            'source_url' => home_url($plan['page']),
        ], $slot);
    } catch (\Throwable $e) {
        continue;
    }
    if (!$booking instanceof Booking) {
        continue;
    }
    $booking->created_at = gmdate('Y-m-d H:i:s', $booked);
    $booking->updated_at = $status === 'scheduled' ? $booking->created_at : gmdate('Y-m-d H:i:s', min(time() - 600, $start + 2 * HOUR_IN_SECONDS));
    $booking->status = $status;
    $booking->save();
    // The entry keeps its booking, as FluentBooking saves it on submit.
    $data[$field]['booking_id'] = $booking->id;
    \FluentForm\App\Models\Submission::where('id', $entryId)->update(['response' => wp_json_encode($data, JSON_UNESCAPED_UNICODE)]);
    \FluentForm\App\Helpers\Helper::setSubmissionMeta($entryId, 'fluent_booking_id', $booking->id, $form->id);
    do_action('fluent_booking/after_booking_scheduled_async', $booking->id, $slot->id);
    as_unschedule_action('fluent_booking/after_booking_scheduled_async', [$booking->id, $slot->id], 'fluent-booking');
    anypress_sample_mail_at(gmdate('Y-m-d H:i:s', $booked));
    // As the feed would have: the client is a contact, tagged by what they need.
    if ($tags && function_exists('FluentCrmApi')) {
        $contact = FluentCrmApi('contacts')->createOrUpdate([
            'first_name' => $p[0],
            'last_name' => $p[1],
            'email' => anypress_sample_email($p),
            'phone' => $data['phone'],
            'status' => 'subscribed',
        ]);
        if ($contact) {
            $contact->attachTags([$tags[$areas[$a][0]]]);
        }
    }
}

// Reminders for consultations already past would all go out at once on the first visit.
if (class_exists('ActionScheduler')) {
    foreach (as_get_scheduled_actions(['group' => 'fluent-booking', 'status' => \ActionScheduler_Store::STATUS_PENDING, 'per_page' => 500], 'ids') as $actionId) {
        $next = \ActionScheduler::store()->get_date($actionId);
        if ($next && $next->getTimestamp() < time() + 300) {
            \ActionScheduler::store()->cancel_action($actionId);
        }
    }
}
