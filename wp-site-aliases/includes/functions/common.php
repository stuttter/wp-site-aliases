<?php

/**
 * Site Aliases Functions
 *
 * @package Plugins/Site/Aliases/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the site ID being modified
 *
 * @since 1.0.0
 *
 * @return int
 */
function wp_site_aliases_get_site_id() {

	// Set the default
	$default_id = is_blog_admin()
		? get_current_blog_id()
		: 0;

	// Get site ID being requested
	$site_id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: $default_id;

	// Look for alias ID requests
	if ( empty( $site_id ) ) {
		$alias_id = wp_site_aliases_sanitize_alias_ids( true );

		// Found an alias ID
		if ( ! empty( $alias_id ) ) {
			$alias   = WP_Site_Alias::get_instance( $alias_id );
			$site_id = $alias->site_id;
		}
	}

	// No site ID
	if ( empty( $site_id ) && ! wp_site_aliases_is_network_list() ) {
		wp_die( esc_html__( 'Invalid site ID.', 'wp-site-aliases' ) );
	}

	// Get the blog details
	$details = get_blog_details( $site_id );

	// No blog details
	if ( empty( $details ) ) {
		wp_die( esc_html__( 'Invalid site ID.', 'wp-site-aliases' ) );
	}

	// Return the blog ID
	return (int) $details->blog_id;
}

/**
 * Validate alias parameters
 *
 * @since 1.0.0
 *
 * @param  array  $args  Raw input parameters
 *
 * @return array|WP_Error Validated parameters on success, WP_Error otherwise
 */
function wp_site_aliases_validate_alias_parameters( $args = array() ) {

	// Parse the args
	$r = wp_parse_args( $args, array(
		'site_id' => 0,
		'domain'  => '',
		'status'  => '',
	) );

	// Cast site ID to int
	$r['site_id'] = (int) $r['site_id'];

	// Remove all whitespace from domain
	$r['domain'] = preg_replace( '/\s+/', '', $r['domain'] );

	// Strip schemes from domain
	$r['domain'] = preg_replace( '#^https?://#', '', rtrim( $r['domain'], '/' ) );

	// Make domain lowercase
	$r['domain'] = strtolower( $r['domain'] );

	// Bail if site ID is not valid
	if ( empty( $r['site_id'] ) ) {
		return new WP_Error( 'wp_site_aliases_alias_invalid_id', esc_html__( 'Invalid site ID', 'wp-site-aliases' ) );
	}

	// Prevent debug notices
	if ( empty( $r['domain'] ) ) {
		return new WP_Error( 'wp_site_aliases_domain_empty', esc_html__( 'Aliases require a domain name', 'wp-site-aliases' ) );
	}

	// Bail if no domain name
	if ( ! strpos( $r['domain'], '.' ) ) {
		return new WP_Error( 'wp_site_aliases_domain_requires_tld', esc_html__( 'Aliases require a top-level domain', 'wp-site-aliases' ) );
	}

	// Bail if domain name using invalid characters
	if ( ! preg_match( '#^[a-z0-9\-.]+$#i', $r['domain'] ) ) {
		return new WP_Error( 'wp_site_aliases_domain_invalid_chars', esc_html__( 'Aliases can only contain alphanumeric characters, dashes (-) and periods (.)', 'wp-site-aliases' ) );
	}

	// Validate status
	if ( ! in_array( $r['status'], array( 'active', 'inactive' ), true ) ) {
		return new WP_Error( 'wp_site_aliases_domain_invalid_status', esc_html__( 'Status must be active or inactive', 'wp-site-aliases' ) );
	}

	return $r;
}

/**
 * Wrapper for admin URLs
 *
 * @since 1.0.0
 *
 * @param array $args
 * @return array
 */
function wp_site_aliases_admin_url( $args = array() ) {

	// Network aliases?
	$network_aliases = wp_site_aliases_is_network_list();

	// Parse args
	$r = wp_parse_args( $args, array(
		'id'   => wp_site_aliases_get_site_id(),
		'page' => ( true === $network_aliases )
			? 'all_site_aliases'
			: 'site_aliases',
	) );

	// File
	$file = ( true === $network_aliases )
		? 'admin.php'
		: 'sites.php';

	// Override for network edit
	if ( wp_site_aliases_is_network_edit() ) {
		$file = 'admin.php';
		$r['page'] = 'all_site_aliases';
	}

	// Location
	$admin_url = is_network_admin()
		? network_admin_url( $file )
		: admin_url( 'index.php' );

	// Unset ID if viewing network aliases
	if ( true === $network_aliases ) {
		unset( $r['id'] );
	}

	// Add query args
	$url = add_query_arg( $r, $admin_url );

	// Add args and return
	return apply_filters( 'wp_site_aliases_admin_url', $url, $admin_url, $r, $args );
}

/**
 * Check if a domain has a alias available
 *
 * @since 1.0.0
 *
 * @param stdClass|null $site Site object if already found, null otherwise
 *
 * @param string $domain Domain we're looking for
 *
 * @return stdClass|null Site object if already found, null otherwise
 */
function wp_site_aliases_check_domain_alias( $site, $domain ) {

	// Have we already matched? (Allows other plugins to match first)
	if ( ! empty( $site ) ) {
		return $site;
	}

	// Get the alias
	$alias = WP_Site_Alias::get_by_domain( array(
		maybe_add_www( $domain ),
		maybe_strip_www( $domain )
	) );

	// Bail if no alias
	if ( empty( $alias ) || is_wp_error( $alias ) ) {
		return $site;
	}

	// Ignore non-active aliases
	if ( 'active' !== $alias->status ) {
		return $site;
	}

	// Fetch the actual data for the site
	$aliased_site = get_blog_details( $alias->site_id );
	if ( empty( $aliased_site ) ) {
		return $site;
	}

	return $aliased_site;
}

/**
 * Clear aliases for a site when it's deleted
 *
 * @param int $site_id Site being deleted
 */
function wp_site_aliases_clear_aliases_on_delete( $site_id = 0 ) {
	$aliases = WP_Site_Alias::get_by_site( $site_id );

	// Bail if no aliases
	if ( empty( $aliases ) ) {
		return;
	}

	// Loop through aliases & delete them one by one
	foreach ( $aliases as $alias ) {
		$error = $alias->delete();

		if ( is_wp_error( $error ) ) {
			$message = sprintf(
				__( 'Unable to delete alias %d for site %d', 'wp-site-aliases' ),
				$alias->id,
				$site_id
			);
			trigger_error( $message, E_USER_WARNING );
		}
	}
}

/**
 * Register filters for URLs, if we've mapped
 *
 * @since 1.0.0
 */
function wp_site_aliases_register_url_filters() {

	// Look for aliases
	$current_site = $GLOBALS['current_blog'];
	$real_domain  = $current_site->domain;
	$domain       = $_SERVER['HTTP_HOST'];

	// Bail if not mapped
	if ( $domain === $real_domain ) {
		return;
	}

	// Grab both WWW and no-WWW
	$alias = WP_Site_Alias::get_by_domain( array(
		maybe_add_www( $domain ),
		maybe_strip_www( $domain )
	) );

	// Bail if no active alias
	if ( empty( $alias ) || is_wp_error( $alias ) || ( 'active' !== $alias->status ) ) {
		return;
	}

	// Set global for future mappings
	$GLOBALS['wp_current_site_alias'] = $alias;

	// Filter home & site URLs
	add_filter( 'site_url', 'wp_site_aliases_mutate_site_url', -PHP_INT_MAX, 4 );
	add_filter( 'home_url', 'wp_site_aliases_mutate_site_url', -PHP_INT_MAX, 4 );

	// If on main site of network, also filter network urls
	if ( is_main_site() ) {
		add_filter( 'network_site_url', 'wp_site_aliases_mutate_network_url', -PHP_INT_MAX, 3 );
		add_filter( 'network_home_url', 'wp_site_aliases_mutate_network_url', -PHP_INT_MAX, 3 );
	}
}

/**
 * Mutate the home URL to give our primary domain
 *
 * @since 1.0.0
 *
 * @param  string       $url          The complete home URL including scheme and path.
 * @param  string       $path         Path relative to the home URL. Blank string if no path is specified.
 * @param  string|null  $orig_scheme  Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
 * @param  int|null     $site_id      Blog ID, or null for the current blog.
 *
 * @return string Mangled URL
 */
function wp_site_aliases_mutate_site_url( $url, $path = '', $orig_scheme = '', $site_id = 0 ) {

	// Set to current site if empty
	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	// Get the current alias
	$current_alias = $GLOBALS['wp_current_site_alias'];

	// Bail if no alias
	if ( empty( $current_alias ) || ( $site_id !== $current_alias->site_id ) ) {
		return $url;
	}

	// Alias the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$alias_home   = $current_alias->domain . '/';
	$url          = str_replace( $current_home, $alias_home, $url );

	return $url;
}

/**
 * Mutate the home URL to give our primary domain
 *
 * @since 1.0.0
 *
 * @param string $url The complete URL including scheme and path.
 *
 * @return string Mutated URL
 */
function wp_site_aliases_mutate_network_url( $url ) {

	// Get current alias & network
	$current_alias   = $GLOBALS['wp_current_site_alias'];
	$current_network = get_current_site();

	// Bail if no alias
	if ( empty( $current_alias ) || ( (int) $current_network->id !== (int) $current_alias->get_network_id() ) ) {
		return $url;
	}

	// Alias the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$alias_home   = $current_alias->domain . '/';
	$url          = str_replace( $current_home, $alias_home, $url );

	return $url;
}

/**
 * Check if a domain belongs to a mapped site
 *
 * @since 1.0.0
 *
 * @param  stdClass|null  $network  Site object if already found, null otherwise
 * @param  string         $domain   Domain we're looking for
 *
 * @return stdClass|null Site object if already found, null otherwise
 */
function wp_site_aliases_check_aliases_for_site( $site, $domain, $path, $path_segments ) {
	global $current_blog, $current_site;

	// Have we already matched? (Allows other plugins to match first)
	if ( ! empty( $site ) ) {
		return $site;
	}

	// Get possible domains and look for aliases
	$domains = wp_site_aliases_get_possible_domains( $domain );
	$alias   = WP_Site_Alias::get_by_domain( $domains );

	// Bail if no alias
	if ( empty( $alias ) || is_wp_error( $alias ) ) {
		return $site;
	}

	// Ignore non-active aliases
	if ( 'active' !== $alias->status ) {
		return $site;
	}

	// Set site & network
	$site         = get_blog_details( $alias->site_id );
	$current_site = get_network( $site->site_id );

	// We found a network, now check for the site. Replace mapped domain with
	// network's original to find.
	$mapped_domain = $alias->domain;
	$subdomain     = substr( $domain, 0, -strlen( $mapped_domain ) );
	$domain        = $subdomain . $current_site->domain;
	$current_blog  = get_site_by_path( $domain, $path, $path_segments );

	// Return site or network
	switch ( current_filter() ) {
		case 'pre_get_site_by_path' :
			return $current_blog;
		case 'pre_get_network_by_path' :
			return $current_site;
		default :
			return $site;
	}
}

/**
 * Get all possible aliases which may be in use and apply to the supplied domain
 *
 * This will return an array of domains which might have been mapped but also apply to the current domain
 * i.e. a given url of site.network.com should return both site.network.com and network.com
 *
 * @since 1.0.0
 *
 * @param  $domain
 *
 * @return array
 */
function wp_site_aliases_get_possible_domains( $domain = '' ) {

	// Strip www. early; we'll still look for it later
	$no_www = maybe_strip_www( $domain );

	// Explode domain on tld and return an array element for each explode point
	// Ensures subdomains of a mapped network are matched
	$domains   = wp_site_aliases_explode_domain( $no_www );
	$additions = array();

	// Also look for www variant of each possible domain
	foreach ( $domains as $current ) {
		$additions[] = maybe_add_www( $current );
	}

	$domains = array_merge( $domains, $additions );

	return $domains;
}

/**
 * Explode a given domain into an array of domains with decreasing number of segments
 *
 * site.network.com should return site.network.com and network.com
 *
 * @since 1.0.0
 *
 * @param  string  $domain    A url to explode, i.e. site.example.com
 * @param  int     $segments  Number of segments to explode and return
 *
 * @return array Exploded urls
 */
function wp_site_aliases_explode_domain( $domain, $segments = 1 ) {

	$host_segments = explode( '.', trim( $domain, '.' ), (int) $segments );

	// Determine what domains to search for. Grab as many segments of the host
	// as asked for.
	$domains = array();

	while ( count( $host_segments ) > 1 ) {
		$domains[] = array_shift( $host_segments ) . '.' . implode( '.', $host_segments );
	}

	// Add the last part, avoiding trailing dot
	$domains[] = array_shift( $host_segments );

	return $domains;
}

/**
 * Get sites for network
 *
 * @since 1.0.0
 *
 * @param  array $args
 *
 * @return array
 */
function wp_site_aliases_get_sites( $args = array() ) {

	// Filter default arguments
	$defaults = apply_filters( 'wp_site_aliases_get_sites', array(
		'network_id' => get_current_network_id(),
		'number'     => 500
	) );

	// Parse arguments
	$r = wp_parse_args( $args, $defaults );

	// Get sites
	return get_sites( $r );
}

/**
 * Is this the all aliases screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_site_aliases_is_network_list() {
	return isset( $_GET['page'] ) && ( 'all_site_aliases' === $_GET['page'] );
}

/**
 * Is this the network alias edit screen?
 *
 * @since 1.0.0
 *
 * @return bool
 */
function wp_site_aliases_is_network_edit() {
	return isset( $_GET['referrer'] ) && ( 'network' === $_GET['referrer'] );
}

/**
 * Get all available site alias statuses
 *
 * @since 1.0.0
 *
 * @return array
 */
function wp_site_aliases_get_statuses() {
	return apply_filters( 'wp_site_aliases_get_statuses', array(
		(object) array(
			'id'   => 'active',
			'name' => _x( 'Active', 'site aliases', 'wp-site-aliases' )
		),
		(object) array(
			'id'   => 'inactive',
			'name' => _x( 'Inactive', 'site aliases', 'wp-site-aliases' )
		),
	) );
}

/**
 * Sanitize requested alias ID values
 *
 * @since 1.0.0
 *
 * @param bool $single
 * @return mixed
 */
function wp_site_aliases_sanitize_alias_ids( $single = false ) {

	// Default value
	$retval = array();

	//
	if ( isset( $_REQUEST['alias_ids'] ) ) {
		$retval = array_map( 'absint', (array) $_REQUEST['alias_ids'] );
	}

	// Return the first item
	if ( true === $single ) {
		$retval = reset( $retval );
	}

	// Filter & return
	return apply_filters( 'wp_site_aliases_sanitize_alias_ids', $retval );
}

/**
 * Maybe remove "www." from domain
 *
 * @since 1.0.0
 *
 * @param  string  $domain
 * @return string
 */
function maybe_strip_www( $domain = '' ) {

	// Remove "www."
	if ( substr( $domain, 0, 4 ) === 'www.' ) {
		$domain = substr( $domain, 4 );
	}

	return $domain;
}

/**
 * Maybe add "www." to domain
 *
 * @since 1.0.0
 *
 * @param  string  $domain
 * @return string
 */
function maybe_add_www( $domain = '' ) {

	// Add "www."
	if ( ! substr( $domain, 0, 4 ) === 'www.' ) {
		$domain = 'www.' . substr( $domain, 4 );
	}

	return $domain;
}

/**
 * Retrieves site alias data given a site alias ID or site alias object.
 *
 * Site alias data will be cached and returned after being passed through a filter.
 *
 * @since 1.0.0
 *
 * @param WP_Site_Alias|int|null $alias Optional. Site alias to retrieve.
 * @return WP_Site_Alias|null The site object or null if not found.
 */
function get_site_alias( $alias = null ) {
	if ( empty( $alias ) ) {
		return null;
	}

	if ( $alias instanceof WP_Site_Alias ) {
		$_alias = $alias;
	} elseif ( is_object( $alias ) ) {
		$_alias = new WP_Site_Alias( $alias );
	} else {
		$_alias = WP_Site_Alias::get_instance( $alias );
	}

	if ( ! $_alias ) {
		return null;
	}

	/**
	 * Fires after a site alias is retrieved.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Site_Alias $_alias Site alias data.
	 */
	$_alias = apply_filters( 'get_site_alias', $_alias );

	return $_alias;
}
