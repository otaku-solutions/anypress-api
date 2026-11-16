<?php
/**
 * FluentEvent: the community's events, each posted in a space as an event post (FluentCommunity's Events tab and the
 * space's calendar), with members going or, for past ones, checked in; and a public calendar of free classes and a
 * meetup on the site, with readers signed up, and the Events page that lists them. Everything goes through
 * FluentEvent's and FluentCommunity's own REST routes, as their screens do.
 */
$plan = $sample['community']['events'] ?? [];
$admin = 1;
$api = 'anypress_sample_api';
$as = static function (int $userId, callable $do) {
    $before = get_current_user_id();
    wp_set_current_user($userId);
    try {
        return $do();
    } finally {
        wp_set_current_user($before);
    }
};
$zone = 'America/New_York';
/** A start and end in the event's own time zone, $days from today at $hour UTC. */
$window = static function (float $days, int $hour, int $hours, string $zone): array {
    $start = (new DateTimeImmutable('@' . strtotime(anypress_sample_when(-$days, $hour) . ' UTC')))->setTimezone(new DateTimeZone($zone));
    return [$start->format('Y-m-d H:i:s'), $start->modify("+$hours hours")->format('Y-m-d H:i:s')];
};
$covers = ['community/04.webp', 'community/06.webp', 'community/00.webp', 'community/14.webp'];
$cover = static function (int $n, string $title) use ($covers): string {
    $id = anypress_sample_image($covers[$n % count($covers)], 0, $title);
    return $id ? (string) wp_get_attachment_url($id) : '';
};
/** A guest added the way the event's Guests screen does: going, or checked in for an event that has happened. */
$guest = static function (int $eventId, array $data, bool $past) use ($api): void {
    // user_id always sent (null for a visitor): FluentEvent's request object keeps the fields of its earlier routes in
    // this one PHP run, so a visitor would take the last member's account.
    $api('POST', "/fluent-event/v2/events/$eventId/guests", array_merge(['user_id' => null], $data, ['status' => $past ? 'checked_in' : 'going']));
};

// The community's events: posted by the site's admin in a space, as FluentCommunity's composer does.
$spaces = class_exists('\FluentCommunity\App\Models\Space')
    ? array_values(\FluentCommunity\App\Models\Space::query()->orderBy('id')->get()->all())
    : [];
$memberIds = array_values(array_filter(array_map(
    static fn ($who) => (int) username_exists(strtolower(implode('', array_slice(is_array($who) ? $sample['readers'][$who[1]] : $sample['people'][$who], 0, 2)))),
    $sample['community']['members'] ?? []
)));
$withEvents = [];
foreach ($plan['community'] ?? [] as $n => [$spaceIndex, $title, $days, $hour, $hours, $caption, $about, $going]) {
    $space = $spaces[$spaceIndex] ?? null;
    if (!$space) {
        continue;
    }
    [$start, $end] = $window($days, $hour, $hours, $zone);
    [, $posted] = $api('POST', '/fluent-community/v2/feeds', [
        'title' => '', 'message' => $caption, 'space' => $space->slug, 'content_type' => 'event',
        'event' => [
            'title' => $title, 'description_md' => $about, 'start_at' => $start, 'end_at' => $end, 'timezone' => $zone,
            'venue_type' => 'online', 'online_meeting_url' => 'https://meet.anypress.example/' . sanitize_title($title),
            'entry_type' => 'rsvp', 'show_guest_count' => 'yes', 'cover_url' => $cover($n, $title),
        ],
    ]);
    $feedId = (int) ($posted['feed']['id'] ?? 0);
    $feed = $feedId ? \FluentCommunity\App\Models\Feed::find($feedId) : null;
    $eventId = (int) ($feed->meta['event_id'] ?? 0);
    if (!$eventId) {
        continue;
    }
    // Members say they're going from the event card; past events have their check-ins.
    foreach ($going as $member) {
        $userId = $memberIds[$member] ?? 0;
        if (!$userId) {
            continue;
        }
        if ($days < 0) {
            $user = get_userdata($userId);
            $guest($eventId, ['name' => $user->display_name, 'email' => $user->user_email, 'user_id' => $userId], true);
        } else {
            // As the event card's Going button records it (FluentEvent's own registration service), without the emails.
            \FluentEvent\App\Services\RegistrationService::registerMember(\FluentEvent\App\Models\Event::find($eventId), get_userdata($userId), 'going', '', ['source' => 'fluent_community', 'source_id' => $feedId]);
        }
    }
    // Posted a week or so before it started.
    $when = anypress_sample_when(max(-$days, 0) + 6 + $n % 4, 14 + $n % 5);
    $feed->created_at = $feed->updated_at = $when;
    $feed->save();
    $withEvents[$space->slug] = true;
}
// Each space with events shows its Events tab (FluentEvent adds it switched off), as the space's menu settings save it.
foreach (array_keys($withEvents) as $slug) {
    [, $menu] = $api('GET', "/fluent-community/v2/spaces/$slug/primary-menu");
    $items = array_map(static fn ($item) => ($item['slug'] ?? '') === 'fluent_events' ? array_merge($item, ['enabled' => 'yes']) : $item, (array) ($menu['menu_items'] ?? []));
    if ($items) {
        $api('POST', "/fluent-community/v2/spaces/$slug/primary-menu", ['menu_items' => $items]);
    }
}

// The public calendar: free classes and a meetup anyone can sign up for, on the site's own event pages.
[, $made] = $api('POST', '/fluent-event/v2/calendars', [
    'title' => 'Classes and meetups', 'slug' => 'classes-and-meetups', 'status' => 'public',
]);
$calendarId = (int) ($made['calendar']['id'] ?? $made['data']['id'] ?? 0);
foreach ($calendarId ? ($plan['public'] ?? []) : [] as $n => [$title, $days, $hour, $hours, $about, $place, $readers]) {
    // Online classes on the community's own clock; the meetup in its city's.
    $local = $place ? ($place[2] ?? $zone) : $zone;
    [$start, $end] = $window($days, $hour, $hours, $local);
    [, $event] = $api('POST', '/fluent-event/v2/events', array_merge([
        'calendar_id' => $calendarId, 'title' => $title, 'description_md' => $about, 'status' => 'public',
        'start_at' => $start, 'end_at' => $end, 'timezone' => $local, 'entry_type' => 'open', 'show_guest_count' => 'yes',
        'cover_url' => $cover($n + 1, $title),
    ], $place
        ? ['venue_type' => 'offline', 'venue' => $place[0], 'address' => $place[1]]
        : ['venue_type' => 'online', 'online_meeting_url' => 'https://meet.anypress.example/' . sanitize_title($title)]));
    $eventId = (int) ($event['event']['id'] ?? $event['data']['id'] ?? 0);
    foreach ($eventId ? $readers : [] as $r) {
        $person = $sample['readers'][$r] ?? null;
        if ($person) {
            $guest($eventId, ['name' => $person[0] . ' ' . $person[1], 'email' => anypress_sample_email($person)], $days < 0);
        }
    }
}

// The Events page FluentEvent makes from its settings, listing the public events (the menu links it, pages.php).
$api('POST', '/fluent-event/v2/settings/create-page', ['key' => 'events_page_id', 'title' => 'Events']);

// Guests signed up over the days before each event (newest closest to it), not all at once, so the dashboard's guest
// trend has a shape.
foreach (\FluentEvent\App\Models\Event::query()->get() as $event) {
    $startsIn = (strtotime($event->start_at . ' UTC') - time()) / DAY_IN_SECONDS;
    foreach (\FluentEvent\App\Models\Guest::where('event_id', $event->id)->orderBy('id')->get() as $g => $row) {
        $when = anypress_sample_when(max(-$startsIn + 1, 0) + 9 - min($g, 8), 12 + $g % 9);
        $row->created_at = $row->updated_at = $when;
        if ($row->status === 'checked_in') {
            $row->checked_in_at = $event->start_at;
        }
        $row->save();
    }
}

// The community's sidebar Events link is the store's own first-run step (mu-plugins/anypress-store.php). The demo's
// set-up ran that step before presets.php turned FluentEvent on in the community, so it runs again on the next request.
update_option('anypress_owner_applied', array_values(array_diff((array) get_option('anypress_owner_applied', []), ['fluent-event'])), false);
