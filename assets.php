<?php

/**
 * Site Aliases Assets
 *
 * @package Plugins/Site/Aliases/Assets
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue admin scripts
 *
 * @since 0.1.0
 */
function wp_site_aliases_admin_enqueue_scripts() {

	// Set location & version for scripts & styles
	$src = wp_site_aliases_get_plugin_url();
	$ver = wp_site_aliases_get_asset_version();

	// Styles
	wp_enqueue_style( 'wp-site-aliases', $src . 'assets/css/site-aliases.css', array(), $ver );
}
