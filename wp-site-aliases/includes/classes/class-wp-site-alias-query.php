<?php
/**
 * Site API: WP_Site_Alias_Query class
 *
 * @package Plugins/Sites/Aliases/Queries
 * @since 1.0.0
 */

/**
 * Core class used for querying aliases.
 *
 * @since 1.0.0
 *
 * @see WP_Site_Alias_Query::__construct() for accepted arguments.
 */
class WP_Site_Alias_Query {

	/**
	 * SQL for database query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $request;

	/**
	 * SQL query clauses.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $sql_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'groupby' => '',
		'orderby' => '',
		'limits'  => '',
	);

	/**
	 * Date query container.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $date_query = false;

	/**
	 * Meta query container.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $meta_query = false;

	/**
	 * Query vars set by the user.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of aliases located by the query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public $aliases;

	/**
	 * The amount of found aliases for the current query.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int
	 */
	public $found_site_aliases = 0;

	/**
	 * The number of pages.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * The database object
	 *
	 * @since 1.0.0
	 *
	 * @var WPDB
	 */
	private $db;

	/**
	 * Sets up the site alias query, based on the query vars passed.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of site alias query parameters. Default empty.
	 *
	 *     @type int          $ID                An alias ID to only return that alias. Default empty.
	 *     @type array        $alias__in         Array of alias IDs to include. Default empty.
	 *     @type array        $alias__not_in     Array of alias IDs to exclude. Default empty.
	 *     @type int          $site_id           A site ID to only return that site. Default empty.
	 *     @type array        $site__in          Array of site IDs to include. Default empty.
	 *     @type array        $site__not_in      Array of site IDs to exclude. Default empty.
	 *     @type bool         $count             Whether to return a site alias count (true) or array of site alias objects.
	 *                                           Default false.
	 *     @type array        $date_query        Date query clauses to limit aliases by. See WP_Date_Query.
	 *                                           Default null.
	 *     @type string       $fields            Site fields to return. Accepts 'ids' (returns an array of site alias IDs)
	 *                                           or empty (returns an array of complete site alias objects). Default empty.
	 *     @type int          $number            Maximum number of aliases to retrieve. Default null (no limit).
	 *     @type int          $offset            Number of aliases to offset the query. Used to build LIMIT clause.
	 *                                           Default 0.
	 *     @type bool         $no_found_rows     Whether to disable the `SQL_CALC_FOUND_ROWS` query. Default true.
	 *     @type string|array $orderby           Site status or array of statuses. Accepts 'id', 'domain', 'status', 'type',
	 *                                           'created', 'domain_length', 'path_length', or 'site__in'. Also accepts false,
	 *                                           an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                           Default 'id'.
	 *     @type string       $order             How to order retrieved aliases. Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type string       $domain            Limit results to those affiliated with a given domain.
	 *                                           Default empty.
	 *     @type array        $domain__in        Array of domains to include affiliated aliases for. Default empty.
	 *     @type array        $domain__not_in    Array of domains to exclude affiliated aliases for. Default empty.
	 *     @type string       $status            Limit results to those affiliated with a given status.
	 *                                           Default empty.
	 *     @type array        $status__in        Array of statuses to include affiliated aliases for. Default empty.
	 *     @type array        $status__not_in    Array of statuses to exclude affiliated aliases for. Default empty.
	 *     @type string       $type              Limit results to those affiliated with a given path.
	 *                                           Default empty.
	 *     @type array        $type__in          Array of types to include affiliated aliases for. Default empty.
	 *     @type array        $type__not_in      Array of types to exclude affiliated aliases for. Default empty.
	 *     @type string       $search            Search term(s) to retrieve matching aliases for. Default empty.
	 *     @type array        $search_columns    Array of column names to be searched. Accepts 'domain', 'status', 'type'.
	 *                                           Default empty array.
	 *
	 *     @type bool         $update_site_alias_cache Whether to prime the cache for found aliases. Default false.
	 * }
	 */
	public function __construct( $query = '' ) {
		$this->db = $GLOBALS['wpdb'];
		$this->query_var_defaults = array(
			'fields'            => '',
			'ID'                => '',
			'alias__in'         => '',
			'alias__not_in'     => '',
			'site_id'           => '',
			'site__in'          => '',
			'site__not_in'      => '',
			'domain'            => '',
			'domain__in'        => '',
			'domain__not_in'    => '',
			'status'            => '',
			'status__in'        => '',
			'status__not_in'    => '',
			'type'              => '',
			'type__in'          => '',
			'type__not_in'      => '',
			'number'            => 100,
			'offset'            => '',
			'orderby'           => 'id',
			'order'             => 'ASC',
			'search'            => '',
			'search_columns'    => array(),
			'count'             => false,
			'date_query'        => null, // See WP_Date_Query
			'meta_query'        => null, // See WP_Meta_Query
			'no_found_rows'     => true,
			'update_site_alias_cache' => true,
		);

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Parses arguments passed to the site alias query with default query parameters.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see WP_Site_Alias_Query::__construct()
	 *
	 * @param string|array $query Array or string of WP_Site_Alias_Query arguments. See WP_Site_Alias_Query::__construct().
	 */
	public function parse_query( $query = '' ) {
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$this->query_vars = wp_parse_args( $query, $this->query_var_defaults );

		/**
		 * Fires after the site alias query vars have been parsed.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Site_Alias_Query &$this The WP_Site_Alias_Query instance (passed by reference).
		 */
		do_action_ref_array( 'parse_site_aliases_query', array( &$this ) );
	}

	/**
	 * Sets up the WordPress query for retrieving aliases.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query Array or URL query string of parameters.
	 * @return array|int List of aliases, or number of aliases when 'count' is passed as a query var.
	 */
	public function query( $query ) {
		$this->query_vars = wp_parse_args( $query );

		return $this->get_site_aliases();
	}

	/**
	 * Retrieves a list of aliases matching the query vars.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array|int List of aliases, or number of aliases when 'count' is passed as a query var.
	 */
	public function get_site_aliases() {
		$this->parse_query();

		/**
		 * Fires before site aliases are retrieved.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Site_Alias_Query &$this Current instance of WP_Site_Alias_Query, passed by reference.
		 */
		do_action_ref_array( 'pre_get_site_aliases', array( &$this ) );

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$key = md5( serialize( wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) ) ) );
		$last_changed = wp_cache_get( 'last_changed', 'blog-aliases' );

		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'blog-aliases' );
		}

		$cache_key   = "get_site_aliases:{$key}:{$last_changed}";
		$cache_value = wp_cache_get( $cache_key, 'blog-aliases' );

		if ( false === $cache_value ) {
			$alias_ids = $this->get_alias_ids();
			if ( $alias_ids ) {
				$this->set_found_site_aliases( $alias_ids );
			}

			$cache_value = array(
				'alias_ids'          => $alias_ids,
				'found_site_aliases' => $this->found_site_aliases,
			);
			wp_cache_add( $cache_key, $cache_value, 'blog-aliases' );
		} else {
			$alias_ids = $cache_value['alias_ids'];
			$this->found_site_aliases = $cache_value['found_site_aliases'];
		}

		if ( $this->found_site_aliases && $this->query_vars['number'] ) {
			$this->max_num_pages = ceil( $this->found_site_aliases / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {
			// $alias_ids is actually a count in this case.
			return intval( $alias_ids );
		}

		$alias_ids = array_map( 'intval', $alias_ids );

		if ( 'ids' == $this->query_vars['fields'] ) {
			$this->aliases = $alias_ids;

			return $this->aliases;
		}

		// Prime site network caches.
		if ( $this->query_vars['update_site_alias_cache'] ) {
			_prime_site_alias_caches( $alias_ids );
		}

		// Fetch full site alias objects from the primed cache.
		$_aliases = array();
		foreach ( $alias_ids as $alias_id ) {
			$_alias = get_site_alias( $alias_id );
			if ( ! empty( $_alias ) ) {
				$_aliases[] = $_alias;
			}
		}

		/**
		 * Filters the site query results.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $results An array of aliases.
		 * @param WP_Site_Alias_Query &$this   Current instance of WP_Site_Alias_Query, passed by reference.
		 */
		$_aliases = apply_filters_ref_array( 'the_site_aliases', array( $_aliases, &$this ) );

		// Convert to WP_Site_Alias instances.
		$this->aliases = array_map( 'get_site_alias', $_aliases );

		return $this->aliases;
	}

	/**
	 * Used internally to get a list of site alias IDs matching the query vars.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return int|array A single count of site alias IDs if a count query. An array of site alias IDs if a full query.
	 */
	protected function get_alias_ids() {
		$order = $this->parse_order( $this->query_vars['order'] );

		// Disable ORDER BY with 'none', an empty array, or boolean false.
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			$orderby = '';
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {
			$ordersby = is_array( $this->query_vars['orderby'] ) ?
				$this->query_vars['orderby'] :
				preg_split( '/[,\s]/', $this->query_vars['orderby'] );

			$orderby_array = array();
			foreach ( $ordersby as $_key => $_value ) {
				if ( ! $_value ) {
					continue;
				}

				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_order   = $order;
				} else {
					$_orderby = $_key;
					$_order   = $_value;
				}

				$parsed = $this->parse_orderby( $_orderby );

				if ( empty( $parsed ) ) {
					continue;
				}

				if ( 'alias__in' === $_orderby || 'site__in' === $_orderby ) {
					$orderby_array[] = $parsed;
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}

			$orderby = implode( ', ', $orderby_array );
		} else {
			$orderby = "ba.id {$order}";
		}

		$number = absint( $this->query_vars['number'] );
		$offset = absint( $this->query_vars['offset'] );

		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		}

		if ( $this->query_vars['count'] ) {
			$fields = 'COUNT(*)';
		} else {
			$fields = 'ba.id';
		}

		/** ID ****************************************************************/

		// Parse site alias IDs for an IN clause.
		$alias_id = absint( $this->query_vars['ID'] );
		if ( ! empty( $alias_id ) ) {
			$this->sql_clauses['where']['ID'] = $this->db->prepare( 'ba.id = %d', $alias_id );
		}

		// Parse site alias IDs for an IN clause.
		if ( ! empty( $this->query_vars['alias__in'] ) ) {
			$this->sql_clauses['where']['alias__in'] = "ba.id IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__in'] ) ) . ' )';
		}

		// Parse site alias IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['alias__not_in'] ) ) {
			$this->sql_clauses['where']['alias__not_in'] = "ba.id NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__not_in'] ) ) . ' )';
		}

		/** Site **************************************************************/

		$site_id = absint( $this->query_vars['site_id'] );
		if ( ! empty( $site_id ) ) {
			$this->sql_clauses['where']['site_id'] = $this->db->prepare( 'ba.blog_id = %d', $site_id );
		}

		// Parse site IDs for an IN clause.
		if ( ! empty( $this->query_vars['site__in'] ) ) {
			$this->sql_clauses['where']['site__in'] = "ba.blog_id IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__in'] ) ) . ' )';
		}

		// Parse site IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['site__not_in'] ) ) {
			$this->sql_clauses['where']['site__not_in'] = "ba.blog_id NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['site__not_in'] ) ) . ' )';
		}

		/** Domain ************************************************************/

		if ( ! empty( $this->query_vars['domain'] ) ) {
			$this->sql_clauses['where']['domain'] = $this->db->prepare( 'ba.domain = %s', $this->query_vars['domain'] );
		}

		// Parse site alias domain for an IN clause.
		if ( is_array( $this->query_vars['domain__in'] ) ) {
			$this->sql_clauses['where']['domain__in'] = "ba.domain IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['domain__in'] ) ) . "' )";
		}

		// Parse site alias domain for a NOT IN clause.
		if ( is_array( $this->query_vars['domain__not_in'] ) ) {
			$this->sql_clauses['where']['domain__not_in'] = "ba.domain NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['domain__not_in'] ) ) . "' )";
		}

		/** Status ************************************************************/

		if ( ! empty( $this->query_vars['status'] ) ) {
			$this->sql_clauses['where']['status'] = $this->db->prepare( 'ba.path = %s', $this->query_vars['status'] );
		}

		// Parse site alias status for an IN clause.
		if ( is_array( $this->query_vars['status__in'] ) ) {
			$this->sql_clauses['where']['status__in'] = "ba.status IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['status__in'] ) ) . "' )";
		}

		// Parse site alias status for a NOT IN clause.
		if ( is_array( $this->query_vars['status__not_in'] ) ) {
			$this->sql_clauses['where']['status__not_in'] = "ba.status NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['status__not_in'] ) ) . "' )";
		}

		/** Type **************************************************************/

		if ( ! empty( $this->query_vars['type'] ) ) {
			$this->sql_clauses['where']['type'] = $this->db->prepare( 'ba.path = %s', $this->query_vars['type'] );
		}

		// Parse site alias type for an IN clause.
		if ( is_array( $this->query_vars['type__in'] ) ) {
			$this->sql_clauses['where']['type__in'] = "ba.type IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['type__in'] ) ) . "' )";
		}

		// Parse site alias type for a NOT IN clause.
		if ( is_array( $this->query_vars['type__not_in'] ) ) {
			$this->sql_clauses['where']['type__not_in'] = "ba.type NOT IN ( '" . implode( "', '", $this->db->_escape( $this->query_vars['type__not_in'] ) ) . "' )";
		}

		/** Search ************************************************************/

		// Falsey search strings are ignored.
		if ( strlen( $this->query_vars['search'] ) ) {
			$search_columns = array();

			if ( $this->query_vars['search_columns'] ) {
				$search_columns = array_intersect( $this->query_vars['search_columns'], array( 'domain', 'status', 'type' ) );
			}

			if ( ! $search_columns ) {
				$search_columns = array( 'domain', 'status', 'type' );
			}

			/**
			 * Filters the columns to search in a WP_Site_Alias_Query search.
			 *
			 * The default columns include 'domain' and 'path.
			 *
			 * @since 1.0.0
			 *
			 * @param array         $search_columns Array of column names to be searched.
			 * @param string        $search         Text being searched.
			 * @param WP_Site_Alias_Query $this           The current WP_Site_Alias_Query instance.
			 */
			$search_columns = apply_filters( 'site_alias_search_columns', $search_columns, $this->query_vars['search'], $this );

			$this->sql_clauses['where']['search'] = $this->get_search_sql( $this->query_vars['search'], $search_columns );
		}

		/** Date **************************************************************/

		$date_query = $this->query_vars['date_query'];
		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			$this->date_query = new WP_Date_Query( $date_query, 'ba.created' );
			$this->sql_clauses['where']['date_query'] = preg_replace( '/^\s*AND\s*/', '', $this->date_query->get_sql() );
		}

		/** Meta **************************************************************/

		$meta_query = $this->query_vars['meta_query'];
		if ( ! empty( $meta_query ) && is_array( $meta_query ) ) {
			$this->meta_query                         = new WP_Meta_Query( $meta_query );
			$clauses                                  = $this->meta_query->get_sql( 'blog_alias', 'ba', 'id', $this );
			$join                                     = $clauses['join'];
			$this->sql_clauses['where']['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $clauses['where'] );
		} else {
			$join = '';
		}

		$where = implode( ' AND ', $this->sql_clauses['where'] );

		$pieces = array( 'fields', 'join', 'where', 'orderby', 'limits', 'groupby' );

		/**
		 * Filters the site alias query clauses.
		 *
		 * @since 1.0.0
		 *
		 * @param array $pieces A compacted array of site alias query clauses.
		 * @param WP_Site_Alias_Query &$this Current instance of WP_Site_Alias_Query, passed by reference.
		 */
		$clauses = apply_filters_ref_array( 'site_alias_clauses', array( compact( $pieces ), &$this ) );

		$fields  = isset( $clauses['fields']  ) ? $clauses['fields']  : '';
		$join    = isset( $clauses['join']    ) ? $clauses['join']    : '';
		$where   = isset( $clauses['where']   ) ? $clauses['where']   : '';
		$orderby = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
		$limits  = isset( $clauses['limits']  ) ? $clauses['limits']  : '';
		$groupby = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';

		if ( $where ) {
			$where = "WHERE {$where}";
		}

		if ( $groupby ) {
			$groupby = "GROUP BY {$groupby}";
		}

		if ( $orderby ) {
			$orderby = "ORDER BY {$orderby}";
		}

		$found_rows = '';
		if ( ! $this->query_vars['no_found_rows'] ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$this->sql_clauses['select']  = "SELECT {$found_rows} {$fields}";
		$this->sql_clauses['from']    = "FROM {$this->db->blog_aliases} ba {$join}";
		$this->sql_clauses['groupby'] = $groupby;
		$this->sql_clauses['orderby'] = $orderby;
		$this->sql_clauses['limits']  = $limits;

		$this->request = "{$this->sql_clauses['select']} {$this->sql_clauses['from']} {$where} {$this->sql_clauses['groupby']} {$this->sql_clauses['orderby']} {$this->sql_clauses['limits']}";

		if ( $this->query_vars['count'] ) {
			return intval( $this->db->get_var( $this->request ) );
		}

		$alias_ids = $this->db->get_col( $this->request );

		return array_map( 'intval', $alias_ids );
	}

	/**
	 * Populates found_site_aliases and max_num_pages properties for the current query
	 * if the limit clause was used.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param  array $alias_ids Optional array of alias IDs
	 */
	private function set_found_site_aliases( $alias_ids = array() ) {

		if ( ! empty( $this->query_vars['number'] ) && ! empty( $this->query_vars['no_found_rows'] ) ) {
			/**
			 * Filters the query used to retrieve found site alias count.
			 *
			 * @since 1.0.0
			 *
			 * @param string              $found_site_aliases_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 * @param WP_Site_Alias_Query $site_alias_query         The `WP_Site_Alias_Query` instance.
			 */
			$found_site_aliases_query = apply_filters( 'found_site_aliases_query', 'SELECT FOUND_ROWS()', $this );

			$this->found_site_aliases = (int) $this->db->get_var( $found_site_aliases_query );
		} elseif ( ! empty( $alias_ids ) ) {
			$this->found_site_aliases = count( $alias_ids );
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $string  Search string.
	 * @param array  $columns Columns to search.
	 * @return string Search SQL.
	 */
	protected function get_search_sql( $string, $columns ) {

		if ( false !== strpos( $string, '*' ) ) {
			$like = '%' . implode( '%', array_map( array( $this->db, 'esc_like' ), explode( '*', $string ) ) ) . '%';
		} else {
			$like = '%' . $this->db->esc_like( $string ) . '%';
		}

		$searches = array();
		foreach ( $columns as $column ) {
			$searches[] = $this->db->prepare( "$column LIKE %s", $like );
		}

		return '(' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Parses and sanitizes 'orderby' keys passed to the site alias query.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {

		$parsed = false;

		switch ( $orderby ) {
			case 'id':
				$parsed = 'ba.id';
				break;
			case 'site_id':
				$parsed = 'ba.blog_id';
				break;
			case 'alias__in':
				$alias__in = implode( ',', array_map( 'absint', $this->query_vars['alias__in'] ) );
				$parsed = "FIELD( ba.id, $alias__in )";
				break;
			case 'site__in':
				$site__in = implode( ',', array_map( 'absint', $this->query_vars['site__in'] ) );
				$parsed = "FIELD( ba.blog_id, $site__in )";
				break;
			case 'domain':
			case 'created':
			case 'status':
			case 'type':
				$parsed = $orderby;
				break;
			case 'domain_length':
				$parsed = 'CHAR_LENGTH(domain)';
				break;
		}

		return $parsed;
	}

	/**
	 * Parses an 'order' query variable and cast it to 'ASC' or 'DESC' as necessary.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'ASC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
