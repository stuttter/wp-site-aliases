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
 * @since 0.1.0
 */
class WP_Site_Alias {

	/**
	 * Alias data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 *
	 * @param array $data Alias data
	 */
	protected function __construct( $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Clone magic method when clone( self ) is called.
	 *
	 * As the internal data is stored in an object, we have to make a copy
	 * when this object is cloned.
	 *
	 * @since 0.1.0
	 */
	public function __clone() {
		$this->data = clone( $this->data );
	}

	/**
	 * Get alias ID
	 *
	 * @since 0.1.0
	 *
	 * @return int Alias ID
	 */
	public function get_id() {
		return (int) $this->data->id;
	}

	/**
	 * Get site ID
	 *
	 * @since 0.1.0
	 *
	 * @return int Site ID
	 */
	public function get_site_id() {
		return (int) $this->data->blog_id;
	}

	/**
	 * Get the domain from the alias
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_domain() {
		return maybe_strip_www( $this->data->domain );
	}

	/**
	 * Get the alias created date
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	public function get_created() {
		return $this->data->created;
	}

	/**
	 * Get the alias status
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	public function get_status() {
		return $this->data->status;
	}

	/**
	 * Set the status for the alias
	 *
	 * @since 0.1.0
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
	 * @since 0.1.0
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
	 * @since 0.1.0
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
		if ( ! empty( $data['domain'] ) && ( $this->data->domain !== $data['domain'] ) ) {

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
		if ( ! empty( $data['status'] ) && ( $this->data->status !== $data['status'] ) ) {
			$fields['status'] = sanitize_key( $data['status'] );
			$formats[]        = '%s';
		}

		// Do we have things to update?
		if ( empty( $fields ) ) {
			return false;
		}

		$id           = $this->get_id();
		$where        = array( 'id' => $id );
		$where_format = array( '%d' );
		$result       = $wpdb->update( $wpdb->blog_aliases, $fields, $where, $formats, $where_format );

		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_site_aliases_alias_update_failed' );
		}

		$old_alias = clone( $this );

		// Update internal state
		foreach ( $fields as $key => $val ) {
			$this->data->{$key} = $val;
		}

		$domain = $this->get_domain();

		// Delete the ID cache
		wp_cache_delete( "id:{$id}", 'site_aliases' );

		// Update the domain cache
		wp_cache_set( "domain:{$domain}", $this->data, 'site_aliases' );

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
	 * @since 0.1.0
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function delete() {
		global $wpdb;

		$where        = array( 'id' => $this->get_id() );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->blog_aliases, $where, $where_format );

		if ( empty( $result ) ) {
			return new WP_Error( 'wp_site_aliases_alias_delete_failed' );
		}

		// Update the cache
		wp_cache_delete( 'id:'     . $this->get_site_id(), 'site_aliases' );
		wp_cache_delete( 'domain:' . $this->get_domain(),  'site_aliases' );

		/**
		 * Fires after a alias has been delete.
		 *
		 * @param  WP_Site_Alias  $alias The alias object.
		 */
		do_action( 'wp_site_aliases_deleted', $this );

		return true;
	}

	/**
	 * Convert data to Alias instance
	 *
	 * Allows use as a callback, such as in `array_map`
	 *
	 * @since 0.1.0
	 *
	 * @param stdClass $data Raw alias data
	 * @return Alias
	 */
	protected static function to_instance( $data ) {
		return new static( $data );
	}

	/**
	 * Convert list of data to Alias instances
	 *
	 * @since 0.1.0
	 *
	 * @param stdClass[] $data Raw alias rows
	 * @return Alias[]
	 */
	protected static function to_instances( $data ) {
		return array_map( array( get_called_class(), 'to_instance' ), $data );
	}

	/**
	 * Get alias by alias ID
	 *
	 * @since 0.1.0
	 *
	 * @param int|Alias $alias Alias ID or instance
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get( $alias ) {
		global $wpdb;

		// Allow passing a site object in
		if ( $alias instanceof WP_Site_Alias ) {
			return $alias;
		}

		if ( ! is_numeric( $alias ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$alias = absint( $alias );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$alias    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->blog_aliases} WHERE id = %d", $alias ) );

		$wpdb->suppress_errors( $suppress );

		if ( empty( $alias ) ) {
			return null;
		}

		return new static( $alias );
	}

	/**
	 * Get alias by site ID
	 *
	 * @since 0.1.0
	 *
	 * @param int|stdClass $site Site ID, or site object from {@see get_blog_details}
	 *
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_site( $site = null ) {
		global $wpdb;

		// Allow passing a site object in
		if ( is_object( $site ) && isset( $site->blog_id ) ) {
			$site = $site->blog_id;
		}

		if ( ! is_numeric( $site ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$site = absint( $site );

		// Check cache first
		$aliases = wp_cache_get( "id:{$site}", 'site_aliases' );
		if ( false !== $aliases ) {
			return static::to_instances( $aliases );
		}

		// Cache missed, fetch from DB
		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$aliases  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->blog_aliases} WHERE blog_id = %d", $site ) );

		$wpdb->suppress_errors( $suppress );

		if ( empty( $aliases ) ) {
			return null;
		}

		wp_cache_set( "id:{$site}", $aliases, 'site_aliases' );

		return static::to_instances( $aliases );
	}

	/**
	 * Get alias by domain(s)
	 *
	 * @since 0.1.0
	 *
	 * @param string|array $domains Domain(s) to match against
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_domain( $domains = array() ) {
		global $wpdb;

		$domains = (array) $domains;

		// Check cache first
		$not_exists = 0;
		foreach ( $domains as $domain ) {
			$data = wp_cache_get( "domain:{$domain}", 'site_aliases' );

			if ( ! empty( $data ) && ( 'notexists' !== $data ) ) {
				return new static( $data );
			} elseif ( 'notexists' === $data ) {
				$not_exists++;
			}
		}

		// Every domain was found in the cache, but doesn't exist
		if ( $not_exists === count( $domains ) ) {
			return null;
		}

		$placeholders    = array_fill( 0, count( $domains ), '%s' );
		$placeholders_in = implode( ',', $placeholders );

		// Prepare the query
		$query = "SELECT * FROM {$wpdb->blog_aliases} WHERE domain IN ($placeholders_in) ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1";
		$query = $wpdb->prepare( $query, $domains );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$alias    = $wpdb->get_row( $query );

		$wpdb->suppress_errors( $suppress );

		// Cache that it doesn't exist
		if ( empty( $alias ) ) {
			foreach ( $domains as $domain ) {
				wp_cache_set( "domain:{$domain}", 'notexists', 'site_aliases' );
			}

			return null;
		}

		wp_cache_set( "domain:{$alias->domain}", $alias, 'site_aliases' );

		return new static( $alias );
	}

	/**
	 * Create a new domain alias
	 *
	 * @param $site Site ID, or site object from {@see get_blog_details}
	 * @return Alias|WP_Error
	 */
	public static function create( $site, $domain, $status ) {
		global $wpdb;

		// Allow passing a site object in
		if ( is_object( $site ) && isset( $site->blog_id ) ) {
			$site = $site->blog_id;
		}

		if ( ! is_numeric( $site ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$site   = absint( $site );
		$status = sanitize_key( $status );

		// Did we get a full URL?
		if ( strpos( $domain, '://' ) !== false ) {
			$domain = parse_url( $domain, PHP_URL_HOST );
		}

		// Does this domain exist already?
		$existing = static::get_by_domain( $domain );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		// Domain exists already...
		if ( ! empty( $existing ) ) {

			if ( $site !== $existing->get_site_id() ) {
				return new WP_Error( 'wp_site_aliases_alias_domain_exists', esc_html__( 'That alias is already in use.', 'wp-site-aliases' ) );
			}

			// ...and points to this site, so nothing to do
			return $existing;
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
			array( '%d', '%s', '%s', '%d' )
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
		wp_cache_delete( "id:{$site}",       'site_aliases' );
		wp_cache_delete( "domain:{$domain}", 'site_aliases' );

		$alias = static::get( $wpdb->insert_id );

		/**
		 * Fires after a alias has been created.
		 *
		 * @param  WP_Site_Alias  $alias  The alias object.
		 */
		do_action( 'wp_site_aliases_created', $alias );

		return $alias;
	}
}
