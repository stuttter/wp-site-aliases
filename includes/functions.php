<?php

/**
 * Check if a domain has a alias available
 *
 * @since 0.1.0
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

	// Grab both WWW and no-WWW
	if ( strpos( $domain, 'www.' ) === 0 ) {
		$www    = $domain;
		$no_www = substr( $domain, 4 );
	} else {
		$no_www = $domain;
		$www    = 'www.' . $domain;
	}

	// Get the alias
	$alias = WP_Site_Alias::get_by_domain( array( $www, $no_www ) );

	// Bail if no alias
	if ( empty( $alias ) || is_wp_error( $alias ) ) {
		return $site;
	}

	// Ignore non-active aliases
	if ( ! $alias->is_active() ) {
		return $site;
	}

	// Fetch the actual data for the site
	$aliased_site = get_blog_details( $alias->get_site_id() );
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

	if ( empty( $aliases ) ) {
		return;
	}

	foreach ( $aliases as $alias ) {
		$error = $alias->delete();

		if ( is_wp_error( $error ) ) {
			$message = sprintf(
				__( 'Unable to delete alias %d for site %d', 'wp-site-aliases' ),
				$alias->get_id(),
				$site_id
			);
			trigger_error( $message, E_USER_WARNING );
		}
	}
}

/**
 * Register filters for URLs, if we've mapped
 *
 * @since 0.1.0
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
	if ( strpos( $domain, 'www.' ) === 0 ) {
		$www   = $domain;
		$nowww = substr( $domain, 4 );
	} else {
		$nowww = $domain;
		$www   = 'www.' . $domain;
	}

	$alias = WP_Site_Alias::get_by_domain( array( $www, $nowww ) );
	if ( empty( $alias ) || is_wp_error( $alias ) || ! $alias->is_active() ) {
		return;
	}

	// Set global for future mappings
	$GLOBALS['wp_current_site_alias'] = $alias;

	// Skip canonical for now
	remove_filter( 'template_redirect', 'redirect_canonical' );

	// Filter home & site URLs
	add_filter( 'site_url', 'wp_site_aliases_mangle_site_url', -PHP_INT_MAX, 4 );
	add_filter( 'home_url', 'wp_site_aliases_mangle_site_url', -PHP_INT_MAX, 4 );

	// If on main site of network, also filter network urls
	if ( is_main_site() ) {
		add_filter( 'network_site_url', 'wp_site_aliases_mangle_network_url', -PHP_INT_MAX, 3 );
		add_filter( 'network_home_url', 'wp_site_aliases_mangle_network_url', -PHP_INT_MAX, 3 );
	}
}

/**
 * Mangle the home URL to give our primary domain
 *
 * @since 0.1.0
 *
 * @param  string       $url          The complete home URL including scheme and path.
 * @param  string       $path         Path relative to the home URL. Blank string if no path is specified.
 * @param  string|null  $orig_scheme  Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
 * @param  int|null     $site_id      Blog ID, or null for the current blog.
 *
 * @return string Mangled URL
 */
function wp_site_aliases_mangle_site_url( $url, $path, $orig_scheme, $site_id = 0 ) {

	// Set to current site if empty
	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	// Get the current alias
	$current_alias = $GLOBALS['wp_current_site_alias'];

	// Bail if no alias
	if ( empty( $current_alias ) || ( $site_id !== $current_alias->get_site_id() ) ) {
		return $url;
	}

	// Alias the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$alias_home   = $current_alias->get_domain() . '/';
	$url          = str_replace( $current_home, $alias_home, $url );

	return $url;
}

/**
 * Check if a domain belongs to a mapped site
 *
 * @since 0.1.0
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
	if ( ! $alias->is_active() ) {
		return $site;
	}

	// Set site & network
	$site         = get_blog_details( $alias->get_site_id() );
	$current_site = wp_get_network( $site->site_id );

	// We found a network, now check for the site. Replace mapped domain with
	// network's original to find.
	$mapped_domain = $alias->get_domain();
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
 * Mangle the home URL to give our primary domain
 *
 * @since 0.1.0
 *
 * @param  string       $url          The complete home URL including scheme and path.
 * @param  string       $path         Path relative to the home URL. Blank string if no path is specified.
 * @param  string|null  $orig_scheme  Scheme to give the home URL context. Accepts 'http', 'https', 'relative' or null.
 * @param  int|null     $site_id      Blog ID, or null for the current blog.
 *
 * @return string Mangled URL
 */
function wp_site_aliases_mangle_network_url( $url, $path, $orig_scheme, $site_id ) {

	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	$current_alias   = $GLOBALS['wp_current_site_alias'];
	$current_network = get_current_site();

	if ( empty( $current_alias ) || (int) $current_network->id !== (int) $current_alias->get_network_id() ) {
		return $url;
	}

	// Alias the URLs
	$current_home = $GLOBALS['current_blog']->domain . $GLOBALS['current_blog']->path;
	$alias_home   = $current_alias->get_domain() . '/';
	$url          = str_replace( $current_home, $alias_home, $url );
}

/**
 * Get all possible aliases which may be in use and apply to the supplied domain
 *
 * This will return an array of domains which might have been mapped but also apply to the current domain
 * i.e. a given url of site.network.com should return both site.network.com and network.com
 *
 * @since 0.1.0
 *
 * @param  $domain
 *
 * @return array
 */
function wp_site_aliases_get_possible_domains( $domain = '' ) {

	$no_www = maybe_strip_www( $domain );

	// Explode domain on tld and return an array element for each explode point
	// Ensures subdomains of a mapped network are matched
	$domains   = wp_site_aliases_explode_domain( $no_www );
	$additions = array();

	// Also look for www variant of each possible domain
	foreach ( $domains as $current ) {
		$additions[] = 'www.' . $current ;
	}

	$domains = array_merge( $domains, $additions );

	return $domains;
}

/**
 * Explode a given domain into an array of domains with decreasing number of segments
 *
 * site.network.com should return site.network.com and network.com
 *
 * @since 0.1.0
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
 * Maybe remove "www." from domain
 *
 * @since 0.1.0
 *
 * @param  string  $domain
 * @return string
 */
function maybe_strip_www( $domain = '' ) {

	// Remove www.
	if ( substr( $domain, 0, 4 ) === 'www.' ) {
		$domain = substr( $domain, 4 );
	}

	return $domain;
}
