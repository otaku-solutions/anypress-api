<?php
/**
 * An industry demo's look ('kit'): the theme's kit for the trade (styles/kit-<kit>.json), as picking it in the Site
 * Editor's styles does. First, so the parts after it (a chart's colours, say) take its palette.
 */
$kitFile = preg_match('/^[a-z0-9-]+$/', (string) $sample['kit']) ? get_theme_file_path('styles/kit-' . $sample['kit'] . '.json') : '';
if ($kitFile && is_file($kitFile)) {
    $kit = json_decode((string) file_get_contents($kitFile), true) ?: [];
    $user = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
    $styles = array_replace_recursive(json_decode((string) get_post_field('post_content', $user), true) ?: [], array_intersect_key($kit, array_flip(['settings', 'styles'])));
    $styles['version'] = WP_Theme_JSON::LATEST_SCHEMA;
    $styles['isGlobalStylesUserThemeJSON'] = true;
    wp_update_post(['ID' => $user, 'post_content' => wp_slash(wp_json_encode($styles))]);
    wp_clean_theme_json_cache();
}
