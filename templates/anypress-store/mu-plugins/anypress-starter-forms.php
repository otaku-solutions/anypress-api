<?php
/*
 * Plugin Name: AnyPress starter forms
 * Description: Puts Fluent Forms' and FluentBooking's own blocks into the spots the AnyPress theme's starter pages leave for them (Request a quote, Book a consultation and the rest), making each form from Fluent Forms' own fields the first time a page that needs it is saved. Part of the store template.
 */

/*
 * A spot is a group block with the class "anypress-slot" and a name: "Form: <key>" for one of the forms below, or
 * "Booking calendar" for the store's booking calendar. When a page or post with a spot is saved, the spot's contents
 * become the plugin's block (the form, or FluentBooking's list of the calendar's booking types) and the spot keeps its
 * look; a spot whose plugin isn't on, or a calendar spot before the owner has made a calendar, stays as it is. Spots
 * still waiting show nothing to visitors, and the calendar shows as soon as there is one. The forms are made once, the
 * first time a saved page needs them, so a store's Fluent Forms list only has the forms its pages use.
 */

/**
 * The starter pages' forms, by key: [title, submit button, message after sending, fields]. A field is
 * [element, name, label, required, choices or help text]. Every form asks for a name and an email first and ends with
 * the newsletter box, so the CRM lifecycle (anypress-crm-lifecycle.php) treats it like the contact form.
 */
function anypress_slot_forms(): array
{
    $phone = ['input_text', 'phone', __('Phone Number', 'anypress'), false, ''];
    $message = fn (string $label, string $help = '') => ['textarea', 'message', $label, false, $help];

    return [
        'quote' => [__('Request a quote', 'anypress'), __('Send my request', 'anypress'), __('Thank you. We will look at the details and send your quote within two working days.', 'anypress'), [
            $phone,
            ['select', 'service', __('What Do You Need?', 'anypress'), true, [__('First service', 'anypress'), __('Second service', 'anypress'), __('Third service', 'anypress'), __('Something else', 'anypress')]],
            ['textarea', 'details', __('Tell Us About the Job', 'anypress'), true, __('What it is, how big, and anything we should know.', 'anypress')],
            ['input_text', 'location', __('Where Is It?', 'anypress'), false, __('Town or postcode.', 'anypress')],
            ['input_radio', 'timing', __('When Do You Need It?', 'anypress'), false, [__('As soon as possible', 'anypress'), __('Within a month', 'anypress'), __('In the next few months', 'anypress'), __('Just planning', 'anypress')]],
            ['select', 'budget', __('Rough Budget', 'anypress'), false, [__('Under $1,000', 'anypress'), __('$1,000 to $5,000', 'anypress'), __('$5,000 to $20,000', 'anypress'), __('Over $20,000', 'anypress'), __('Not sure yet', 'anypress')]],
            ['input_radio', 'reply', __('How Should We Reply?', 'anypress'), false, [__('Email', 'anypress'), __('Phone call', 'anypress'), __('Text message', 'anypress')]],
        ]],
        'project' => [__('Project inquiry', 'anypress'), __('Send my project', 'anypress'), __('Thank you. We read every project ourselves and reply within two working days, with questions or a time to talk.', 'anypress'), [
            ['input_text', 'company', __('Company or Organisation', 'anypress'), false, ''],
            ['input_url', 'website', __('Your Current Website, If You Have One', 'anypress'), false, ''],
            ['input_checkbox', 'help', __('What Can We Help With?', 'anypress'), true, [__('A new project', 'anypress'), __('Improving something you have', 'anypress'), __('Ongoing help', 'anypress'), __('Advice first', 'anypress')]],
            ['textarea', 'goals', __('What Should the Project Achieve?', 'anypress'), true, __('The problem to solve and what success looks like.', 'anypress')],
            ['select', 'budget', __('Budget', 'anypress'), false, [__('Under $5,000', 'anypress'), __('$5,000 to $15,000', 'anypress'), __('$15,000 to $50,000', 'anypress'), __('Over $50,000', 'anypress'), __('Not sure yet', 'anypress')]],
            ['input_date', 'deadline', __('Is There a Deadline?', 'anypress'), false, __('Leave it empty if there isn\'t one.', 'anypress')],
            ['input_radio', 'source', __('How Did You Hear About Us?', 'anypress'), false, [__('Search', 'anypress'), __('A recommendation', 'anypress'), __('Social media', 'anypress'), __('Something else', 'anypress')]],
        ]],
        'onboarding' => [__('Client onboarding', 'anypress'), __('Send my details', 'anypress'), __('Thank you, that is everything we need. We will be in touch to book your first meeting.', 'anypress'), [
            $phone,
            ['input_text', 'company', __('Business Name', 'anypress'), true, ''],
            ['address', 'address', __('Business Address', 'anypress'), false, ''],
            ['input_url', 'website', __('Website', 'anypress'), false, ''],
            ['input_text', 'billing', __('Who Should Invoices Go To?', 'anypress'), false, __('Name and email, if it isn\'t you.', 'anypress')],
            ['textarea', 'goals', __('What Do You Want to Get from Working with Us?', 'anypress'), true, ''],
            ['textarea', 'access', __('Accounts and Tools We Will Need', 'anypress'), false, __('List them here. Never type passwords into a form: we will ask for access safely.', 'anypress')],
            ['input_radio', 'contact', __('How Do You Like to Be Kept Up to Date?', 'anypress'), false, [__('Email', 'anypress'), __('Phone call', 'anypress'), __('A short call each week', 'anypress')]],
        ]],
        'valuation' => [__('Home valuation', 'anypress'), __('Ask for my valuation', 'anypress'), __('Thank you. An agent will call you within one working day to arrange a visit.', 'anypress'), [
            $phone,
            ['input_radio', 'looking', __('Are You Buying or Selling?', 'anypress'), true, [__('Selling', 'anypress'), __('Buying', 'anypress'), __('Both', 'anypress'), __('Renting out', 'anypress')]],
            ['address', 'address', __('The Property\'s Address', 'anypress'), false, ''],
            ['select', 'type', __('Type of Home', 'anypress'), false, [__('House', 'anypress'), __('Townhouse', 'anypress'), __('Apartment', 'anypress'), __('Land', 'anypress'), __('Other', 'anypress')]],
            ['select', 'bedrooms', __('Bedrooms', 'anypress'), false, ['1', '2', '3', '4', __('5 or more', 'anypress')]],
            ['input_radio', 'timing', __('When Are You Thinking of Moving?', 'anypress'), false, [__('Within 3 months', 'anypress'), __('3 to 6 months', 'anypress'), __('6 to 12 months', 'anypress'), __('Just curious', 'anypress')]],
            $message(__('Anything Else We Should Know?', 'anypress')),
        ]],
        'order' => [__('Pickup order', 'anypress'), __('Place my order', 'anypress'), __('Thank you. We will text you to confirm your order and its time. You pay when you collect it or when it arrives.', 'anypress'), [
            ['input_text', 'phone', __('Phone Number', 'anypress'), true, __('We text you when your order is ready.', 'anypress')],
            ['input_radio', 'collect', __('Pickup or Delivery?', 'anypress'), true, [__('Pickup', 'anypress'), __('Delivery', 'anypress')]],
            ['address', 'address', __('Delivery Address', 'anypress'), false, 'when:collect=' . __('Delivery', 'anypress')],
            ['textarea', 'items', __('What Would You Like?', 'anypress'), true, __('One item per line, with how many. For example: 2 margherita, 1 garden salad.', 'anypress')],
            ['input_date', 'pickup', __('Day and Time You Want It', 'anypress'), true, ''],
            ['input_checkbox', 'dietary', __('Any Allergies or Dietary Needs?', 'anypress'), false, [__('Vegetarian', 'anypress'), __('Vegan', 'anypress'), __('Gluten free', 'anypress'), __('Nut allergy', 'anypress'), __('Dairy free', 'anypress')]],
            $message(__('Notes for the Kitchen', 'anypress')),
        ]],
        'visit' => [__('Plan your visit', 'anypress'), __('Let us know', 'anypress'), __('Thank you. Someone will look out for you at the door and save you a seat.', 'anypress'), [
            $phone,
            ['select', 'service', __('Which Service Will You Come To?', 'anypress'), false, [__('Sunday 9am', 'anypress'), __('Sunday 11am', 'anypress'), __('Not sure yet', 'anypress')]],
            ['input_number', 'adults', __('How Many Adults?', 'anypress'), false, ''],
            ['input_number', 'children', __('How Many Children?', 'anypress'), false, __('Children have their own groups during the service.', 'anypress')],
            ['textarea', 'prayer', __('Is There Anything We Can Pray About for You?', 'anypress'), false, __('Optional, and kept private to the pastoral team.', 'anypress')],
        ]],
        'consultation' => [__('Consultation request', 'anypress'), __('Send my request', 'anypress'), __('Thank you. We will check for conflicts and call you within one working day. Pick a time below if you have not yet.', 'anypress'), [
            $phone,
            ['input_radio', 'area', __('What Do You Need Help With?', 'anypress'), true, [__('Family law', 'anypress'), __('Business law', 'anypress'), __('Wills and estates', 'anypress'), __('Criminal defense', 'anypress'), __('Something else', 'anypress')]],
            ['textarea', 'parties', __('Who Else Is Involved?', 'anypress'), false, __('Names of the other people or companies, so we can check we have never acted for them.', 'anypress')],
            ['input_radio', 'court', __('Is There a Court Date or Deadline Coming Up?', 'anypress'), false, [__('Yes', 'anypress'), __('No', 'anypress'), __('Not sure', 'anypress')]],
            ['input_date', 'court_date', __('When Is It?', 'anypress'), false, 'when:court=' . __('Yes', 'anypress')],
            ['textarea', 'summary', __('A Few Lines About Your Situation', 'anypress'), true, __('Keep the details for the meeting itself.', 'anypress')],
            ['input_radio', 'reply', __('How Should We Contact You?', 'anypress'), false, [__('Phone call', 'anypress'), __('Email', 'anypress')]],
            ['terms_and_condition', 'disclaimer', __('Disclaimer', 'anypress'), true, __('I understand that asking for a consultation does not make you my lawyers until we both sign an engagement letter.', 'anypress')],
        ]],
    ];
}

/** The form for a spot: made the first time it is needed, from Fluent Forms' own default fields. 0 without Fluent Forms. */
function anypress_slot_form(string $key): int
{
    $forms = anypress_slot_forms();
    if (!isset($forms[$key]) || !defined('FLUENTFORM') || !class_exists('\FluentForm\App\Services\Form\FormService')) {
        return 0;
    }
    $made = (array) get_option('anypress_slot_forms', []);
    if (!empty($made[$key]) && \FluentForm\App\Models\Form::find((int) $made[$key])) {
        return (int) $made[$key];
    }
    // One save at a time makes forms, so two saves never both make one or drop each other's from the list. WordPress's
    // own lock (an atomic row in wp_options, freed after two minutes if a save dies); a save that can't get it leaves
    // the spot waiting for the next save.
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    if (!\WP_Upgrader::create_lock('anypress_slot_forms', 120)) {
        return 0;
    }
    $mine = anypress_slot_option('anypress_slot_forms.lock');
    try {
        return anypress_slot_form_make($key, $forms[$key]);
    } finally {
        // Only the lock this save took: after two minutes another save may hold it.
        if (anypress_slot_option('anypress_slot_forms.lock') === $mine) {
            \WP_Upgrader::release_lock('anypress_slot_forms');
        }
    }
}

/** An option as it is in the database now, not as this request cached it (or cached it missing). */
function anypress_slot_option(string $name)
{
    wp_cache_delete($name, 'options');
    $missing = wp_cache_get('notoptions', 'options');
    if (is_array($missing) && isset($missing[$name])) {
        unset($missing[$name]);
        wp_cache_set('notoptions', $missing, 'options');
    }
    return get_option($name, null);
}

/** Makes the form for a spot and adds it to the list; called only while holding the lock. 0 if anything fails. */
function anypress_slot_form_make(string $key, array $form_config): int
{
    $made = (array) anypress_slot_option('anypress_slot_forms');
    if (!empty($made[$key]) && \FluentForm\App\Models\Form::find((int) $made[$key])) {
        return (int) $made[$key];
    }
    [$title, $button, $thanks, $fields] = $form_config;
    $defaults = fluentformLoadFile('Services/FormBuilder/DefaultElements.php');
    $n = 0;
    $choices = fn (array $list) => array_map(fn ($o, $i) => ['label' => $o, 'value' => $o, 'calc_value' => '', 'image' => '', 'id' => $i], $list, array_keys($list));
    $make = function (string $element, array $over) use ($defaults, &$n) {
        $group = isset($defaults['general'][$element]) ? 'general' : 'advanced';
        $field = array_replace_recursive($defaults[$group][$element], $over);
        if (isset($over['settings']['advanced_options'])) {
            $field['settings']['advanced_options'] = $over['settings']['advanced_options'];
        }
        unset($field['editor_options']['template_options']);
        $field['uniqElKey'] = 'el_anypress_' . (++$n);
        return $field;
    };
    $required = fn (bool $on) => ['required' => ['value' => $on, 'message' => __('This field is required', 'anypress'), 'global' => true]];
    $built = [
        $make('input_name', ['settings' => ['admin_field_label' => __('Name', 'anypress')], 'fields' => [
            'first_name' => ['attributes' => ['placeholder' => ''], 'settings' => ['label' => __('What Is Your Name?', 'anypress'), 'validation_rules' => $required(true)]],
            'last_name' => ['settings' => ['visible' => false]],
        ]]),
        $make('input_email', ['attributes' => ['placeholder' => ''], 'settings' => ['label' => __('Your Email Address', 'anypress'), 'validation_rules' => ['required' => ['value' => true]]]]),
    ];
    foreach ($fields as [$element, $name, $label, $must, $extra]) {
        $over = ['attributes' => ['name' => $name], 'settings' => ['label' => $label, 'admin_field_label' => $label, 'validation_rules' => $required($must)]];
        if (is_array($extra)) {
            $over['settings']['advanced_options'] = $choices($extra);
            if ($element === 'select') {
                $over['settings']['placeholder'] = __('Choose one', 'anypress');
            }
        } elseif (strpos($extra, 'when:') === 0) {
            // Shown only for one answer to an earlier question ("when:collect=Delivery").
            [$field, $value] = explode('=', substr($extra, 5), 2);
            $over['settings']['conditional_logics'] = ['type' => 'any', 'status' => true, 'conditions' => [['field' => $field, 'value' => $value, 'operator' => '=']]];
        } elseif ($element === 'terms_and_condition') {
            $over['settings'] = ['tnc_html' => esc_html($extra), 'admin_field_label' => $label, 'validation_rules' => $required($must)];
        } elseif ($extra !== '') {
            $over['settings']['help_message'] = $extra;
        }
        if ($element === 'textarea') {
            $over['attributes']['rows'] = 4;
        }
        if ($element === 'address') {
            // The address fields keep their own labels; the group gets this one.
            $over = ['attributes' => ['name' => $name], 'settings' => ['label' => $label, 'admin_field_label' => $label] + array_intersect_key($over['settings'], ['conditional_logics' => 1])];
        }
        if ($element === 'input_date') {
            $over['settings']['date_format'] = $name === 'deadline' ? 'm/d/Y' : 'm/d/Y h:i K';
            $over['settings']['is_time_enabled'] = $name !== 'deadline';
        }
        $built[] = $make($element, $over);
    }
    $built[] = $make('input_checkbox', [
        'attributes' => ['name' => 'newsletter'],
        'settings' => ['label' => __('Newsletter', 'anypress'), 'admin_field_label' => __('Newsletter', 'anypress'), 'label_placement' => 'hide_label',
            'advanced_options' => $choices([__('Send me tips and news by email (you can stop any time)', 'anypress')]) ],
    ]);
    $built[count($built) - 1]['settings']['advanced_options'][0]['value'] = 'yes';

    // A Fluent Forms error must never stop the page saving: the spot waits, and a half-made form is removed.
    $form = null;
    try {
        $form = (new \FluentForm\App\Services\Form\FormService())->store(['predefined' => 'blank_form', 'type' => 'form']);
        $structure = json_decode($form->form_fields, true);
        $structure['fields'] = $built;
        $structure['submitButton']['settings']['button_ui']['text'] = $button;
        // The theme's own button (colours, radius, font), not Fluent Forms' blue, as on the contact form (crm/setup.php).
        foreach (['background_color', 'color'] as $colour) {
            $structure['submitButton']['settings'][$colour] = '';
        }
        $structure['submitButton']['attributes']['class'] = 'wp-element-button';
        $form->title = $title;
        $form->form_fields = wp_json_encode($structure);
        $form->save();
        $settings = \FluentForm\App\Models\FormMeta::where('form_id', $form->id)->where('meta_key', 'formSettings')->first();
        if ($settings) {
            $value = json_decode($settings->value, true);
            $value['confirmation']['messageToShow'] = $thanks;
            $value['confirmation']['samePageFormBehavior'] = 'hide_form';
            $settings->value = wp_json_encode($value);
            $settings->save();
        }
        // One question at a time, like a short survey, with a progress bar: Fluent Forms' own conversational form
        // (free), shown inline by its block. Its colours come from the theme (theme.json styles .ff_conv_app_*).
        \FluentForm\App\Models\FormMeta::persist($form->id, 'is_conversion_form', 'yes');
        \FluentForm\App\Models\FormMeta::persist($form->id, 'ffc_form_settings_design', [
            'background_color' => '', 'question_color' => '', 'answer_color' => '', 'button_color' => '', 'button_text_color' => '',
            'background_image' => '', 'background_brightness' => 0, 'disable_branding' => 'yes', 'hide_media_on_mobile' => 'no',
            'key_hint' => 'no', 'enable_scroll_to_top' => 'no',
        ]);
        // Added to the list as it is now, so nothing another save added since is lost.
        $made = (array) anypress_slot_option('anypress_slot_forms');
        $made[$key] = (int) $form->id;
        // Its entries count as enquiries only once it is on the list (anypress-crm-lifecycle.php).
        if (!update_option('anypress_slot_forms', $made, false)) {
            throw new \RuntimeException('could not save the form list');
        }
    } catch (\Throwable $e) {
        if ($form && !empty($form->id)) {
            try {
                \FluentForm\App\Models\FormMeta::where('form_id', $form->id)->delete();
                $form->delete();
            } catch (\Throwable $ignored) {
            }
        }
        error_log('anypress-starter-forms: ' . $e->getMessage());
        return 0;
    }

    return (int) $form->id;
}

/** The store's first active booking calendar, or 0. */
function anypress_slot_calendar(): int
{
    static $id = null;
    if ($id === null) {
        $calendar = class_exists('\FluentBooking\App\Models\Calendar')
            ? \FluentBooking\App\Models\Calendar::where('status', 'active')->where('type', '!=', 'team')->orderBy('id', 'ASC')->first()
            : null;
        $id = $calendar ? (int) $calendar->id : 0;
    }
    return $id;
}

/** A spot's key ("Form: quote" gives "quote", "Booking calendar" gives "booking"), or '' when the block isn't a spot. */
function anypress_slot_key(array $block): string
{
    if (($block['blockName'] ?? '') !== 'core/group' || !preg_match('/(^|\s)anypress-slot(\s|$)/', (string) ($block['attrs']['className'] ?? ''))) {
        return '';
    }
    $name = (string) ($block['attrs']['metadata']['name'] ?? '');
    if ($name === 'Booking calendar') {
        return 'booking';
    }
    return preg_match('/^Form: ([a-z]+)$/', $name, $m) ? $m[1] : '';
}

/** The plugin's block for a spot, or null while it can't have one yet. */
function anypress_slot_block(string $key): ?array
{
    if ($key === 'booking') {
        $calendar = anypress_slot_calendar();
        $attrs = ['calendarId' => (string) $calendar, 'eventIds' => ['all'], 'hideInfo' => true];
        $name = 'fluent-booking/calendar-management';
    } else {
        $calendar = anypress_slot_form($key);
        $attrs = ['formId' => (string) $calendar];
        $name = 'fluentfom/guten-block';
    }
    return $calendar ? ['blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []] : null;
}

/** Fills every spot it can in a list of blocks. The spot keeps its look (the theme's card) without the spot class. */
function anypress_fill_slots(array $blocks, bool &$changed): array
{
    foreach ($blocks as &$block) {
        $key = anypress_slot_key($block);
        $fill = $key !== '' ? anypress_slot_block($key) : null;
        if ($fill) {
            $block['attrs']['className'] = trim(preg_replace('/(^|\s)anypress-slot(?=\s|$)/', ' ', $block['attrs']['className']));
            if ($block['attrs']['className'] === '') {
                unset($block['attrs']['className']);
            }
            // An empty spot saves as one piece of HTML: cut it at its closing tag.
            if (count($block['innerContent']) < 2) {
                $html = (string) reset($block['innerContent']);
                $cut = (int) strrpos($html, '</');
                [$open, $close] = [substr($html, 0, $cut), substr($html, $cut)];
            } else {
                $open = (string) reset($block['innerContent']);
                $close = (string) end($block['innerContent']);
            }
            $open = preg_replace('/ anypress-slot(?=[\s"])/', '', $open, 1);
            $block['innerBlocks'] = [$fill];
            $block['innerContent'] = [$open, null, $close];
            $block['innerHTML'] = $open . $close;
            $changed = true;
        } elseif (!empty($block['innerBlocks'])) {
            $block['innerBlocks'] = anypress_fill_slots($block['innerBlocks'], $changed);
        }
    }
    unset($block);
    return $blocks;
}

add_filter('wp_insert_post_data', function (array $data): array {
    if (!in_array($data['post_type'] ?? '', ['page', 'post'], true) || strpos((string) $data['post_content'], 'anypress-slot') === false) {
        return $data;
    }
    $changed = false;
    $blocks = anypress_fill_slots(parse_blocks(wp_unslash($data['post_content'])), $changed);
    if ($changed) {
        $data['post_content'] = wp_slash(serialize_blocks($blocks));
    }
    return $data;
}, 10, 1);

// Spots that are still waiting: the calendar shows once there is one; anything else shows nothing to visitors.
add_filter('render_block_core/group', function (string $html, array $block): string {
    $key = anypress_slot_key($block);
    if ($key === '' || is_admin() || wp_is_serving_rest_request()) {
        return $html;
    }
    $fill = $key === 'booking' ? anypress_slot_block('booking') : null;
    if (!$fill) {
        return '';
    }
    $changed = false;
    return render_block(anypress_fill_slots([$block], $changed)[0]);
}, 10, 2);
