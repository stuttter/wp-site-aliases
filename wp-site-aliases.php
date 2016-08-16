<?php

/**
 * Plugin Name: WP Site Aliases
 * Plugin URI:  http://wordpress.org/plugins/wp-site-aliases/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Allow parent users to manage their children
 * Version:     2.0.0
 * Text Domain: wp-site-aliases
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Define the table variables
if ( empty( $GLOBALS['wpdb']->blog_aliases ) ) {
	$GLOBALS['wpdb']->blog_aliases       = $GLOBALS['wpdb']->base_prefix . 'blog_aliases';
	$GLOBALS['wpdb']->ms_global_tables[] = 'blog_aliases';
}

// Ensure cache is shared
wp_cache_add_global_groups( array( 'site_aliases', 'network_aliases' ) );

// Get the plugin path
$plugin_path = dirname( __FILE__ ) . '/';

// Classes
require_once $plugin_path . 'includes/class-wp-site-alias.php';
require_once $plugin_path . 'includes/class-wp-site-aliases-query.php';
require_once $plugin_path . 'includes/class-wp-site-alias-network.php';
require_once $plugin_path . 'includes/class-wp-site-aliases-db-table.php';

// Required Files
require_once $plugin_path . 'includes/admin.php';
require_once $plugin_path . 'includes/assets.php';
require_once $plugin_path . 'includes/capabilities.php';
require_once $plugin_path . 'includes/functions.php';
require_once $plugin_path . 'includes/hooks.php';

/**
 * Return the plugin's root file
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_file() {
	return __FILE__;
}

/**
 * Return the plugin's URL
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_site_aliases_get_plugin_url() {
	return plugin_dir_url( wp_site_aliases_get_plugin_file() );
}

/**
 * Return the asset version
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_site_aliases_get_asset_version() {
	return 201603080001;
}
