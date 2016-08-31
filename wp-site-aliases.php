<?php

/**
 * Plugin Name: WP Site Aliases
 * Plugin URI:  http://wordpress.org/plugins/wp-site-aliases/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: User-defined site-aliases for your multisite installation
 * Version:     1.0.0
 * Text Domain: wp-site-aliases
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

//
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
	require_once $plugin_path . 'includes/classes/class-wp-site-alias.php';
	require_once $plugin_path . 'includes/classes/class-wp-site-alias-query.php';
	require_once $plugin_path . 'includes/classes/class-wp-site-aliases-db-table.php';

	// Required Files
	require_once $plugin_path . 'includes/functions/admin.php';
	require_once $plugin_path . 'includes/functions/assets.php';
	require_once $plugin_path . 'includes/functions/capabilities.php';
	require_once $plugin_path . 'includes/functions/common.php';
	require_once $plugin_path . 'includes/functions/hooks.php';

	// Register database table
	if ( empty( $GLOBALS['wpdb']->blog_aliases ) ) {
		$GLOBALS['wpdb']->blog_aliases       = $GLOBALS['wpdb']->base_prefix . 'blog_aliases';
		$GLOBALS['wpdb']->ms_global_tables[] = 'blog_aliases';
	}

	// Register global cache group
	wp_cache_add_global_groups( array( 'blog-aliases' ) );
}

/**
 * Return the path to the plugin's root file
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the path to the plugin's directory
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_path() {
	return dirname( wp_site_aliases_get_plugin_file() ) . '/wp-site-aliases/';
}

/**
 * Return the plugin's URL
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
	return 201608310001;
}
