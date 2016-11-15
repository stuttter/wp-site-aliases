<?php

/**
 * Site Aliases Class
 *
 * @package Plugins/Site/Aliases/Class
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Site Alias Class
 *
 * @since 1.0.0
 */
final class WP_Site_Alias {

	/**
	 * Alias ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int
	 */
	public $id;

	/**
	 * The ID of the alias's site.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $blog_id = 0;

	/**
	 * Domain of the alias.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $domain = '';

	/**
	 * The date on which the alias was created.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string Date in MySQL's datetime format.
	 */
	public $created = '0000-00-00 00:00:00';

	/**
	 * Status of the alias.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $status = '';

	/**
	 * Creates a new WP_Site_Alias object.
	 *
	 * Will populate object properties from the object provided and assign other
	 * default properties based on that information.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_Site_Alias|object $site A site object.
	 */
	public function __construct( $site ) {
		foreach( get_object_vars( $site ) as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * Converts an object to array.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Object as array.
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Getter.
	 *
	 * Allows current multisite naming conventions when getting properties.
	 * Allows access to extended site properties.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $key Property to get.
	 * @return mixed Value of the property. Null if not available.
	 */
	public function __get( $key = '' ) {
		switch ( $key ) {
			case 'id':
			case 'alias_id':
				return (int) $this->id;
			case 'blog_id':
			case 'site_id':
				return (int) $this->blog_id;
		}

		return null;
	}

	/**
	 * Isset-er.
	 *
	 * Allows current multisite naming conventions when checking for properties.
	 * Checks for extended site properties.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $key Property to check if set.
	 * @return bool Whether the property is set.
	 */
	public function __isset( $key = '' ) {
		switch ( $key ) {
			case 'id' :
			case 'alias_id' :
			case 'blog_id' :
			case 'site_id' :
				return true;
		}

		return false;
	}

	/**
	 * Setter.
	 *
	 * Allows current multisite naming conventions while setting properties.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $key   Property to set.
	 * @param mixed  $value Value to assign to the property.
	 */
	public function __set( $key, $value ) {
		switch ( $key ) {
			case 'id' :
			case 'alias_id' :
				$this->id = (int) $value;
				break;
			case 'blog_id' :
			case 'site_id' :
				$this->blog_id = (int) $value;
				break;
			default:
				$this->{$key} = $value;
		}
	}

	/**
	 * Set the status for the alias
	 *
	 * @since 1.0.0
	 *
	 * @param  bool $status Status should be 'active' or 'inactive'
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function set_status( $status = 'active' ) {
		return $this->update( array(
			'status' => $status,
		) );
	}

	/**
	 * Set the domain for the alias
	 *
	 * @since 1.0.0
	 *
	 * @param string $domain Domain name
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function set_domain( $domain ) {
		return $this->update( array(
			'domain' => $domain,
		) );
	}

	/**
	 * Update the alias
	 *
	 * See also, {@see set_domain} and {@see set_status} as convenience methods.
	 *
	 * @since 1.0.0
	 *
	 * @param array|stdClass $data Alias fields (associative array or object properties)
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function update( $data = array() ) {
		global $wpdb;

		$data    = (array) $data;
		$fields  = array();
		$formats = array();

		// Were we given a domain (and is it not the current one?)
		if ( ! empty( $data['domain'] ) && ( $this->domain !== $data['domain'] ) ) {

			// Does this domain exist already?
			$existing = static::get_by_domain( $data['domain'] );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Domain exists already and points to another site
			if ( ! empty( $existing ) ) {
				return new WP_Error( 'wp_site_aliases_alias_domain_exists' );
			}

			// No uppercase letters in domains
			$fields['domain'] = strtolower( $data['domain'] );
			$formats[]        = '%s';
		}

		// Were we given a status (and is it not the current one?)
		if ( ! empty( $data['status'] ) && ( $this->status !== $data['status'] ) ) {
			$fields['status'] = sanitize_key( $data['status'] );
			$formats[]        = '%s';
		}

		// Were we given a site ID (and is it not the current one?)
		if ( ! empty( $data['site_id'] ) && ( $this->site_id !== $data['site_id'] ) ) {
			$fields['blog_id'] = (int) $data['site_id'];
			$formats[]         = '%d';
		}

		// Do we have things to update?
		if ( empty( $fields ) ) {
			return false;
		}

		$alias_id     = $this->id;
		$where        = array( 'id' => $alias_id );
		$where_format = array( '%d' );
		$result       = $wpdb->update( $wpdb->blog_aliases, $fields, $where, $formats, $where_format );

		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_site_aliases_alias_update_failed' );
		}

		// Clone this object
		$old_alias = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->{$key} = $val;
		}

		// Update the alias caches
		wp_cache_set( $alias_id, $this, 'blog-aliases' );
		wp_cache_set( 'last_changed', microtime(), 'blog-aliases' );

		/**
		 * Fires after a alias has been updated.
		 *
		 * @param  WP_Site_Alias  $alias  The alias object.
		 * @param  WP_Site_Alias  $alias  The previous alias object.
		 */
		do_action( 'wp_site_aliases_updated', $this, $old_alias );

		return true;
	}

	/**
	 * Delete the alias
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function delete() {
		global $wpdb;

		// Try to delete the alias
		$alias_id     = $this->id;
		$where        = array( 'id' => $alias_id );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->blog_aliases, $where, $where_format );

		// Bail if no alias to delete
		if ( empty( $result ) ) {
			return new WP_Error( 'wp_site_aliases_alias_delete_failed' );
		}

		// Delete the blog alias cache
		clean_blog_alias_cache( $this );

		/**
		 * Fires after a alias has been delete.
		 *
		 * @param  WP_Site_Alias  $alias The alias object.
		 */
		do_action( 'wp_site_aliases_deleted', $this );

		return true;
	}

	/**
	 * Retrieves a site alias from the database by its ID.
	 *
	 * @static
	 * @since 1.0.0
	 * @access public
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $alias_id The ID of the site to retrieve.
	 * @return WP_Site_Alias|false The site alias's object if found. False if not.
	 */
	public static function get_instance( $alias_id = 0 ) {
		global $wpdb;

		$alias_id = (int) $alias_id;
		if ( empty( $alias_id ) ) {
			return false;
		}

		// Check cache first
		$_alias = wp_cache_get( $alias_id, 'blog-aliases' );

		// No cached alias
		if ( false === $_alias ) {
			$_alias = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->blog_aliases} WHERE id = %d LIMIT 1", $alias_id ) );

			// Bail if no alias found
			if ( empty( $_alias ) || is_wp_error( $_alias ) ) {
				return false;
			}

			// Add alias to cache
			wp_cache_add( $alias_id, $_alias, 'blog-aliases' );
			wp_cache_set( 'last_changed', microtime(), 'blog-aliases' );
		}

		// Return alias object
		return new WP_Site_Alias( $_alias );
	}

	/**
	 * Get alias by site ID
	 *
	 * @since 1.0.0
	 *
	 * @param int|stdClass $site Site ID, or site object from {@see get_blog_details}
	 *
	 * @return WP_Site_Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_site( $site = null ) {

		// Allow passing a site object in
		if ( is_object( $site ) && isset( $site->blog_id ) ) {
			$site = $site->blog_id;
		}

		if ( ! is_numeric( $site ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		// Get aliases
		$aliases = new WP_Site_Alias_Query( array(
			'site_id' => (int) $site
		) );

		// Bail if no aliases
		if ( empty( $aliases->found_site_aliases ) ) {
			return null;
		}

		return $aliases->aliases;
	}

	/**
	 * Get alias by network ID
	 *
	 * @since 1.0.0
	 *
	 * @param int|stdClass $network Network ID, or network object
	 *
	 * @return WP_Site_Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_for_network() {

		// Get site IDs
		$sites    = wp_site_aliases_get_sites();
		$site_ids = wp_list_pluck( $sites, 'blog_id' );

		// Get aliases
		$aliases = new WP_Site_Alias_Query( array(
			'site__in' => $site_ids
		) );

		// Bail if no aliases
		if ( empty( $aliases->found_site_aliases ) ) {
			return null;
		}

		return $aliases->aliases;
	}

	/**
	 * Get alias by domain(s)
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $domains Domain(s) to match against
	 * @return WP_Site_Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_domain( $domains = array() ) {

		// Get aliases
		$aliases = new WP_Site_Alias_Query( array(
			'domain__in' => (array) $domains
		) );

		// Bail if no aliases
		if ( empty( $aliases->found_site_aliases ) ) {
			return null;
		}

		return reset( $aliases->aliases );
	}

	/**
	 * Create a new domain alias
	 *
	 * @param mixed  $site   Site ID, or site object from {@see get_blog_details}
	 * @param string $domain Domain
	 * @param status $status Status of alias
	 *
	 * @return WP_Site_Alias|WP_Error
	 */
	public static function create( $site = 0, $domain = '', $status = 'active' ) {
		global $wpdb;

		// Allow passing a site object in
		if ( is_object( $site ) && isset( $site->blog_id ) ) {
			$site = $site->blog_id;
		}

		// Bail if no site
		if ( ! is_numeric( $site ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$site   = (int) $site;
		$status = sanitize_key( $status );

		// Did we get a full URL?
		if ( strpos( $domain, '://' ) !== false ) {
			$domain = parse_url( $domain, PHP_URL_HOST );
		}

		// Does this domain exist already?
		$existing = static::get_by_domain( $domain );
		if ( is_wp_error( $existing ) ) {
			return $existing;

		// Domain exists already...
		} elseif ( ! empty( $existing ) ) {
			return new WP_Error( 'wp_site_aliases_alias_domain_exists', esc_html__( 'That alias is already in use.', 'wp-site-aliases' ) );
		}

		// Create the alias!
		$prev_errors = ! empty( $GLOBALS['EZSQL_ERROR'] ) ? $GLOBALS['EZSQL_ERROR'] : array();
		$suppress    = $wpdb->suppress_errors( true );
		$result      = $wpdb->insert(
			$wpdb->blog_aliases,
			array(
				'blog_id' => $site,
				'domain'  => $domain,
				'created' => current_time( 'mysql' ),
				'status'  => $status
			),
			array( '%d', '%s', '%s', '%s' )
		);

		$wpdb->suppress_errors( $suppress );

		// Other error. We suppressed errors before, so we need to make sure
		// we handle that now.
		if ( empty( $result ) ) {
			$recent_errors = array_diff_key( $GLOBALS['EZSQL_ERROR'], $prev_errors );

			while ( count( $recent_errors ) > 0 ) {
				$error = array_shift( $recent_errors );
				$wpdb->print_error( $error['error_str'] );
			}

			return new WP_Error( 'wp_site_aliases_alias_insert_failed' );
		}

		// Ensure the cache is flushed
		wp_cache_set( 'last_changed', microtime(), 'blog-aliases' );

		// Get the alias, and prime the caches
		$alias = static::get_instance( $wpdb->insert_id );

		/**
		 * Fires after a alias has been created.
		 *
		 * @param  WP_Site_Alias  $alias  The alias object.
		 */
		do_action( 'wp_site_aliases_created', $alias );

		return $alias;
	}
}
