<?php
/**
 * Uninstall Food Resource Directory Plugin
 *
 * This file runs when the plugin is deleted via the WordPress admin interface.
 * It cleans up all plugin data from the database.
 */

// Exit if accessed directly or if uninstall is not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('frd_version');
delete_option('frd_settings');
delete_option('frd_mapbox_public_token');
delete_option('frd_mapbox_secret_token');

// Delete transients
delete_transient('frd_missing_acf');
delete_transient('frd_activation_notice');

// Delete all food-resource posts and their meta
$args = array(
    'post_type' => 'food-resource',
    'posts_per_page' => -1,
    'post_status' => 'any',
);

$posts = get_posts($args);

foreach ($posts as $post) {
    // Delete post meta (including cached coordinates)
    delete_post_meta($post->ID, '_frd_coordinates');

    // Delete all other post meta
    $meta_keys = get_post_custom_keys($post->ID);
    if (!empty($meta_keys)) {
        foreach ($meta_keys as $meta_key) {
            delete_post_meta($post->ID, $meta_key);
        }
    }

    // Force delete the post (bypass trash)
    wp_delete_post($post->ID, true);
}

// Note: We do NOT delete the custom post type or ACF fields
// because those are managed by ACF and the user may want to keep them
// If you want to delete those too, you'll need to do it manually in ACF

// Clear any cached data
wp_cache_flush();
