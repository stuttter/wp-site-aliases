<?php

/**
 * Plugin Name: WP Site Aliases
 * Plugin URI:  http://wordpress.org/plugins/wp-site-aliases/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Okay, campers... rise & shine! And don't forget your booties 'cause it's cooooold out there today.
 * Version:     2.0.0
 * Text Domain: wp-site-aliases
 * Domain Path: /assets/lang/
 */

/**
 * This is a basic Sunrise drop-in plugin, with just enough functionality to
 * allow WP Site Aliases to work correctly.
 *
 * If your installation already has a file in 'wp-content/sunrise.php' you'll
 * need to figure out the best way to merge this file into yours.
 *
 * If your installation does not have a file in 'wp-content/sunrise.php' you'll
 * want to copy this file to that location.
 *
 * Eitherway, make sure you add the following line to your 'wp-config.php':
 *
 * define( 'SUNRISE', true );
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit();

// WP Site Aliases actions
add_action( 'wp_site_aliases_sunrise', 'wp_site_aliases_maybe_load_current_site_and_network' );

// Sunrise action
do_action( 'wp_site_aliases_sunrise' );

/**
 * Identifies the network and site of a requested domain and path and populates the
 * corresponding network and site global objects as part of the multisite bootstrap process.
 *
 * @since 2.0.0
 *
 * @global WP_Network $current_site The current network.
 * @global WP_Site    $current_blog The current site.
 */
function wp_site_aliases_maybe_load_current_site_and_network() {
	global $current_site, $current_blog, $blog_id, $site_id;

	// Site alias query class has not been loaded yet
	if ( ! class_exists( 'WP_Site_Alias_Query' ) ) {

		// Bail if plugin file cannot be found
		if ( ! file_exists( WP_CONTENT_DIR . '/plugins/wp-site-aliases/wp-site-aliases.php' ) ) {
			return;
		}

		// Require the WP Site Aliases plugin, very early
		require_once WP_CONTENT_DIR . '/plugins/wp-site-aliases/wp-site-aliases.php';
	}

	// Make low & strip slashes
	$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );

	// Maybe remove ports from domain to look for
	if ( substr( $domain, -3 ) === ':80' ) {
		$domain = substr( $domain, 0, -3 );
		$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
	} elseif ( substr( $domain, -4 ) === ':443' ) {
		$domain = substr( $domain, 0, -4 );
		$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
	}

	// Look for an alias
	$aliases = new WP_Site_Alias_Query( array(
		'domain' => $domain,
		'number' => 1
	) );

	// Bail if alias
	if ( empty( $aliases->aliases ) || empty( $aliases->found_site_aliases ) ) {
		return;
	}

	// Get the alias that was found
	$alias = reset( $aliases->aliases );

	// Bail if alias is not active
	if ( 'active' !== $alias->get_status() ) {
		return;
	}

	// Set the site globals
	$blog_id      = $alias->get_site_id();
	$current_blog = get_site( $blog_id );

	// Set the network globals
	$site_id      = $current_blog->site_id;
	$current_site = get_network( $site_id );
}
