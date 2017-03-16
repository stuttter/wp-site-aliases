<?php

/**
 * Plugin Name: WP Site Aliases
 * Plugin URI:  https://wordpress.org/plugins/wp-site-aliases/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: User-defined site-aliases for your multisite installation
 * Version:     5.0.0
 * Text Domain: wp-site-aliases
 * Domain Path: /wp-site-aliases/assets/languages/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Load immediately, to get ahead of everything else
_wp_site_aliases();

/**
 * Wrapper for includes and setting up the database table
 *
 * @since 1.0.0
 */
function _wp_site_aliases() {

	// Get the plugin path
	$plugin_path = wp_site_aliases_get_plugin_path();

	// Classes
	require_once $plugin_path . 'includes/classes/class-wp-db-table.php';
	require_once $plugin_path . 'includes/classes/class-wp-db-table-site-aliases.php';
	require_once $plugin_path . 'includes/classes/class-wp-db-table-site-aliasmeta.php';
	require_once $plugin_path . 'includes/classes/class-wp-site-alias.php';
	require_once $plugin_path . 'includes/classes/class-wp-site-alias-query.php';

	// Required Files
	require_once $plugin_path . 'includes/functions/abstraction.php';
	require_once $plugin_path . 'includes/functions/admin.php';
	require_once $plugin_path . 'includes/functions/assets.php';
	require_once $plugin_path . 'includes/functions/capabilities.php';
	require_once $plugin_path . 'includes/functions/cache.php';
	require_once $plugin_path . 'includes/functions/common.php';
	require_once $plugin_path . 'includes/functions/metadata.php';
	require_once $plugin_path . 'includes/functions/hooks.php';

	// Single-site shims
	if ( ! is_multisite() ) {

		// Avoid BuddyPress abstraction conflicts
		if ( ! function_exists( 'update_blog_status' ) ) {
			require_once ABSPATH . WPINC . '/ms-blogs.php';
		}

		// Make sure class isn't already shimmed
		if ( ! class_exists( 'WP_Site' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-site.php';
		}

		// Make sure class isn't already shimmed
		if ( ! class_exists( 'WP_Network' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-network.php';
		}
	}

	// Tables
	new WP_DB_Table_Site_Aliasmeta();
	new WP_DB_Table_Site_Aliases();

	// Register global cache group
	wp_cache_add_global_groups( array( 'blog-aliases', 'blog_alias_meta' ) );
}

/**
 * Return the path to the plugin root file
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the path to the plugin directory
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_path() {
	return dirname( wp_site_aliases_get_plugin_file() ) . '/wp-site-aliases/';
}

/**
 * Return the plugin URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_url() {
	return plugin_dir_url( wp_site_aliases_get_plugin_file() ) . 'wp-site-aliases/';
}

/**
 * Return the asset version
 *
 * @since 1.0.0
 *
 * @return int
 */
function wp_site_aliases_get_asset_version() {
	return 201703150001;
}
