<?php

/**
 * Site Aliases Cache
 *
 * @package Plugins/Site/Aliases/Cache
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds any site aliases from the given ids to the cache that do not already
 * exist in cache.
 *
 * @since 1.0.0
 * @access private
 *
 * @see update_site_cache()
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $ids ID list.
 */
function _prime_site_alias_caches( $ids = array() ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'blog-aliases' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_aliases = $wpdb->get_results( sprintf( "SELECT * FROM {$wpdb->blog_aliases} WHERE id IN (%s)", join( ",", array_map( 'intval', $non_cached_ids ) ) ) );

		update_site_alias_cache( $fresh_aliases );
	}
}

/**
 * Updates site aliases in cache.
 *
 * @since 1.0.0
 *
 * @param array $aliases Array of site alias objects.
 */
function update_site_alias_cache( $aliases = array() ) {

	// Bail if no aliases
	if ( empty( $aliases ) ) {
		return;
	}

	// Loop through aliases & add them to cache group
	foreach ( $aliases as $alias ) {
		wp_cache_add( $alias->id, $alias, 'blog-aliases' );
	}
}

/**
 * Clean the site alias cache
 *
 * @since 1.0.0
 *
 * @param WP_Site_Alias $alias The alias details as returned from get_site_alias()
 */
function clean_blog_alias_cache( WP_Site_Alias $alias ) {

	// Delete alias from cache group
	wp_cache_delete( $alias->id , 'blog-aliases' );

	/**
	 * Fires immediately after a site alias has been removed from the object cache.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $alias_id Alias ID.
	 * @param WP_Site $alias    Alias object.
	 */
	do_action( 'clean_site_alias_cache', $alias->id, $alias );

	wp_cache_set( 'last_changed', microtime(), 'blog-aliases' );
}
