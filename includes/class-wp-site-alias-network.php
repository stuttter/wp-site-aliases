<?php

/**
 * Site Aliases Network Class
 *
 * @package Plugins/Site/Aliases/Network
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Site Alias Network Class
 *
 * @since 0.1.0
 */
class WP_Site_Alias_Network {

	/**
	 * Prefix on meta keys
	 */
	const KEY_PREFIX = 'wp_site_aliases_';

	/**
	 * Alias ID
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Site ID
	 *
	 * @var int
	 */
	protected $network;

	/**
	 * Alias data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Constructor
	 *
	 * @param int $id Alias ID
	 * @param int $network Network ID
	 * @param array $data Alias data
	 */
	protected function __construct( $id, $network, $data ) {
		$this->id      = $id;
		$this->network = $network;
		$this->data    = (object) $data;
	}

	/**
	 * Get alias ID
	 *
	 * @return int Alias ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Is the alias active?
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->data->active == 1;
	}

	/**
	 * Get network object
	 *
	 * @return stdClass|boolean {@see get_blog_details}
	 */
	public function get_network() {
		return wp_get_network( $this->network );
	}

	/**
	 * Get network ID
	 *
	 * @return int Network ID
	 */
	public function get_network_id() {
		return $this->network;
	}

	/**
	 * Get the domain from the alias
	 *
	 * @return string
	 */
	public function get_domain() {
		return $this->data->domain;
	}

	/**
	 * Set whether the alias is active
	 *
	 * @param bool $active Should the alias be active? (True for active, false for inactive)
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function set_active( $active ) {
		return $this->update( array(
			'active' => (bool) $active,
		) );
	}

	/**
	 * Set the domain for the alias
	 *
	 * @param string $domain Domain name
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
	 * See also, {@see set_domain} and {@see set_active} as convenience methods.
	 *
	 * @param array|stdClass $data Alias fields (associative array or object properties)
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function update( $data ) {
		global $wpdb;

		$data   = (array) $data;
		$fields = array();

		// Were we given a domain (and is it not the current one)?
		if ( ! empty( $data['domain'] ) && ( $this->data->domain !== $data['domain'] ) ) {

			// Did we get a full URL?
			if ( strpos( $data['domain'], '://' ) !== false ) {
				// Parse just the domain out
				$data['domain'] = parse_url( $data['domain'], PHP_URL_HOST );
			}

			// Does this domain exist already?
			$existing = static::get_by_domain( $data['domain'] );
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			// Domain exists already and points to another site
			if ( ! empty( $existing ) ) {
				return new WP_Error( 'wp_site_aliases_alias_domain_exists' );
			}

			$fields['domain'] = $data['domain'];
		}

		// Were we given an active flag (and is it not current)?
		if ( isset( $data['active'] ) && $this->is_active() !== (bool) $data['active'] ) {
			$fields['active'] = (bool) $data['active'];
		}

		// Do we have things to update?
		if ( empty( $fields ) ) {
			return false;
		}

		$current_data = (array) $this->data;
		$new_data = (object) array_merge( $current_data, $fields );
		$fields = array(
			'meta_key'   => static::key_for_domain( $new_data->domain ),
			'meta_value' => serialize( $new_data ),
		);

		$field_formats = array( '%s', '%s' );

		$where  = array( 'meta_id' => $this->get_id() );
		$result = $wpdb->update( $wpdb->sitemeta, $fields, $where, $field_formats, array( '%d' ) );

		if ( empty( $result ) && ! empty( $wpdb->last_error ) ) {
			return new WP_Error( 'wp_site_aliases_alias_update_failed' );
		}

		// Update internal state
		$this->data = $new_data;

		// Update the cache
		wp_cache_delete( 'id:' . $this->get_network_id(), 'network_alias' );
		wp_cache_set( 'domain:' . $fields['meta_key'], $this->data, 'network_alias' );

		return true;
	}

	/**
	 * Delete the alias
	 *
	 * @return bool|WP_Error True if we updated, false if we didn't need to, or WP_Error if an error occurred
	 */
	public function delete() {
		global $wpdb;

		$where        = array( 'meta_id' => $this->get_id() );
		$where_format = array( '%d' );
		$result       = $wpdb->delete( $wpdb->sitemeta, $where, $where_format );

		if ( empty( $result ) ) {
			return new WP_Error( 'wp_site_aliases_alias_delete_failed' );
		}

		// Update the cache
		wp_cache_delete( 'id:' . $this->get_network_id(), 'network_alias' );
		wp_cache_delete( 'domain:' . static::key_for_domain( $this->get_domain() ), 'network_alias' );

		return true;
	}

	/**
	 * Convert data to Alias instance
	 *
	 * Allows use as a callback, such as in `array_map`
	 *
	 * @param stdClass $row Raw alias row
	 * @return Alias
	 */
	protected static function to_instance( $row ) {
		$data = unserialize( $row->meta_value );
		return new static( $row->meta_id, $row->site_id, $data );
	}

	/**
	 * Convert list of data to Alias instances
	 *
	 * @param stdClass[] $rows Raw alias rows
	 * @return Alias[]
	 */
	protected static function to_instances( $rows ) {
		return array_map( array( get_called_class(), 'to_instance' ), $rows );
	}

	/**
	 * Get alias by alias ID
	 *
	 * @param int|Alias $alias Alias ID or instance
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get( $alias ) {
		global $wpdb;

		// Allow passing a site object in
		if ( $alias instanceof Alias ) {
			return $alias;
		}

		if ( ! is_numeric( $alias ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$alias = absint( $alias );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->sitemeta . ' WHERE meta_id = %d', $alias ) );
		$wpdb->suppress_errors( $suppress );

		if ( empty( $row ) ) {
			return null;
		}

		// Double-check that this is one of our fields
		if ( substr( $row->meta_key, 0, strlen( static::KEY_PREFIX ) ) !== static::KEY_PREFIX ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		return static::to_instance( $row );
	}

	/**
	 * Get alias by network ID
	 *
	 * @param int|stdClass $network Network ID, or network object from {@see wp_get_network}
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_network( $network ) {
		global $wpdb;

		// Allow passing a network object in
		if ( is_object( $network ) && isset( $network->id ) ) {
			$network = $network->id;
		}

		if ( ! is_numeric( $network ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$network = absint( $network );

		// Check cache first
		$aliases = wp_cache_get( 'id:' . $network, 'network_alias' );
		if ( ! empty( $aliases ) ) {
			return static::to_instances( $aliases );
		}

		// Cache missed, fetch from DB
		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->sitemeta . ' WHERE site_id = %d AND meta_key LIKE "' . static::KEY_PREFIX . '%%"', $network ) );
		$wpdb->suppress_errors( $suppress );

		if ( empty( $rows ) ) {
			return null;
		}

		wp_cache_set( 'id:' . $network, $rows, 'network_alias' );

		return static::to_instances( $rows );
	}

	/**
	 * Get alias by domain(s)
	 *
	 * @param string|array $domains Domain(s) to match against
	 * @return Alias|WP_Error|null Alias on success, WP_Error if error occurred, or null if no alias found
	 */
	public static function get_by_domain( $domains ) {
		global $wpdb;

		$domains = (array) $domains;
		$keys    = array();

		// Check cache first
		$not_exists = 0;
		foreach ( $domains as $domain ) {

			$key = static::key_for_domain( $domain );
			$row = wp_cache_get( 'domain:' . $key, 'network_alias' );

			if ( ! empty( $row ) && ( $row !== 'notexists' ) ) {
				return static::to_instance( $row );
			} elseif ( $row === 'notexists' ) {
				$not_exists++;
			}

			$keys[] = $key;
		}

		// Every domain we checked was found in the cache, but doesn't exist
		// so skip the query
		if ( $not_exists === count( $domains ) ) {
			return null;
		}

		$placeholders    = array_fill( 0, count( $keys ), '%s' );
		$placeholders_in = implode( ',', $placeholders );

		// Prepare the query
		$query = "SELECT * FROM {$wpdb->sitemeta} WHERE meta_key IN ($placeholders_in) ORDER BY CHAR_LENGTH(meta_value) DESC LIMIT 1";
		$query = $wpdb->prepare( $query, $keys );

		// Suppress errors in case the table doesn't exist
		$suppress = $wpdb->suppress_errors();
		$rows     = $wpdb->get_results( $query );
		$wpdb->suppress_errors( $suppress );

		if ( empty( $rows ) ) {

			// Cache that it doesn't exist
			foreach ( $domains as $domain ) {
				$key = static::key_for_domain( $domain );
				wp_cache_set( 'domain:' . $key, 'notexists', 'network_alias' );
			}

			return null;
		}

		// Grab the longest domain we can
		usort( $rows, array( get_called_class(), 'sort_rows_by_domain_length' ) );
		$row = array_pop( $rows );

		wp_cache_set( 'domain:' . $row->meta_key, $row, 'network_alias' );

		return static::to_instance( $row );
	}

	/**
	 * Get alias by domain, but filter to ensure only active mapped domains are returned
	 *
	 * @param string|array $domains Domain(s) to match against
	 * @return Alias|null Alias on success, or null if no alias found
	 */
	public static function get_active_by_domain( $domains ) {
		$mapped = array();

		foreach ( $domains as $domain ) {
			$single_mapped = self::get_by_domain( array( $domain ) );

			if ( $single_mapped && ! is_wp_error( $single_mapped ) && $single_mapped->is_active() ) {
				$mapped[] = $single_mapped;
			}
		}

		// Grab the longest domain we can
		usort( $mapped, function( $a, $b ) {
			return strlen( $a->get_domain() ) - strlen( $b->get_domain() );
		} );

		return array_pop( $mapped );
	}

	/**
	 * Compare alias rows by domain length
	 *
	 * Comparison callback for `usort`, matches result format of `strcmp`
	 * @param stdClass $a First row
	 * @param stdClass $b Second row
	 * @return int <0 if $a is "less" (shorter), 0 if equal, >0 if $a is "more" (longer)
	 */
	protected static function sort_rows_by_domain_length( $a, $b ) {
		$a_data = unserialize( $a->meta_value );
		$b_data = unserialize( $b->meta_value );

		// Compare by string length; return <0 if $a is shorter, 0 if equal, >0
		// if $a is longer
		return strlen( $a_data->domain ) - strlen( $b_data->domain );
	}

	/**
	 * Create a new domain alias
	 *
	 * @param $site Site ID, or site object from {@see get_blog_details}
	 * @return bool
	 */
	public static function create( $network, $domain, $active = false ) {
		global $wpdb;

		// Allow passing a site object in
		if ( is_object( $network ) && isset( $network->network_id ) ) {
			$network = $network->network_id;
		}

		if ( ! is_numeric( $network ) ) {
			return new WP_Error( 'wp_site_aliases_alias_invalid_id' );
		}

		$network = absint( $network );
		$active = (bool) $active;

		// Did we get a full URL?
		if ( strpos( $domain, '://' ) !== false ) {
			// Parse just the domain out
			$domain = parse_url( $domain, PHP_URL_HOST );
		}

		// Does this domain exist already?
		$existing = static::get_by_domain( $domain );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		if ( ! empty( $existing ) ) {

			// Domain exists and points to another site
			if ( $network !== $existing->get_network_id() ) {
				return new WP_Error( 'wp_site_aliases_alias_domain_exists' );
			}

			return $existing;
		}

		// Create the alias!
		$key  = static::key_for_domain( $domain );
		$data = (object) array(
			'domain' => $domain,
			'active' => $active,
		);

		$result = $wpdb->insert(
			$wpdb->sitemeta,
			array( 'site_id' => $network, 'meta_key' => $key, 'meta_value' => serialize( $data ) ),
			array( '%d', '%s', '%s' )
		);

		if ( empty( $result ) ) {
			return static::create( $network, $domain, $active );
		}

		// Ensure the cache is flushed
		wp_cache_delete( 'id:' . $network, 'network_alias' );
		wp_cache_delete( 'domain:' . $key, 'network_alias' );

		return static::get( $wpdb->insert_id );
	}

	/**
	 * Get the meta key for a given domain
	 *
	 * @param string $domain Domain name
	 * @return string Meta key corresponding to the domain
	 */
	protected static function key_for_domain( $domain ) {
		return static::KEY_PREFIX . sha1( $domain );
	}
}
