<?php

/**
 * Site Aliases Capabilities
 *
 * @package Plugins/Site/Aliases/Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Map site alias meta capabilites
 *
 * @since 0.1.0
 *
 * @param array   $caps
 * @param string  $cap
 * @param int     $user_id
 */
function wp_site_aliases_map_meta_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// One of our caps?
	switch ( $cap ) {

		// Network site edit
		case 'manage_site_aliases' :
		case 'edit_site_aliases' :
			$caps = array( 'manage_site_info' );
			break;

		// Site edit
		case 'manage_aliases' :
		case 'edit_aliases' :
		case 'create_aliases' :
		case 'activate_aliases' :
		case 'deactivate_aliases' :
		case 'delete_aliases' :
			$caps = array( 'manage_options' );
			break;
	}

	// Filter and return
	return apply_filters( 'wp_site_aliases_map_meta_cap', $caps, $cap, $user_id, $args );
}
