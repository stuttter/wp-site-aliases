<?php

/**
 * Site Aliases Abstractions
 *
 * @package Plugins/Site/Aliases/Abstractions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Single-site abstractions of get_blog_details()
 *
 * @since 4.0.0
 *
 * @param mixed $fields
 * @param bool  $get_all
 *
 * @return \WP_Site
 */
function wp_site_aliases_get_site_details( $fields = null, $get_all = true ) {

	// Use regular multisite function
	if ( is_multisite() ) {
		$site = get_blog_details( $fields, $get_all );

	// Shim site details into a proper site object
	} else {
		$url  = parse_url( home_url( '/' ) );
		$site = new WP_Site( (object) array(
			'id'         => get_current_blog_id(),
			'network_id' => get_current_network_id(),
			'domain'     => $url['host'],
			'path'       => $url['path']
		) );
	}

	return $site;
}

/**
 * Single-site abstraction to get $current_blog global
 *
 * @since 4.0.0
 *
 * @return WP_Site
 */
function wp_site_aliases_get_current_site() {
	return is_multisite()
		? $GLOBALS['current_blog']
		: wp_site_aliases_get_site_details();
}
