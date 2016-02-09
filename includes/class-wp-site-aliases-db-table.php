<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Main WP Site Aliases class
 *
 * This class facilitates the following functionality:
 *
 * - Creates & maintains the `wp_blog_aliases` table
 * - Deletes all aliases for sites when sites are deleted
 * - Adds `wp_blog_aliases` to the main database object when appropriate
 *
 * @since 0.1.0
 */
final class WP_Site_Aliases_DB {

	/**
	 * @var string Plugin version
	 */
	public $version = '0.1.0';

	/**
	 * @var string Database version
	 */
	public $db_version = 201602090001;

	/**
	 * @var string Database version key
	 */
	public $db_version_key = 'wpdb_site_aliases_version';

	/**
	 * @var object Database object (usually $GLOBALS['wpdb'])
	 */
	private $db = false;

	/** Methods ***************************************************************/

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// Activation hook
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Setup plugin
		$this->db = $GLOBALS['wpdb'];

		// Force table on to the global database object
		add_action( 'init',           array( $this, 'add_table_to_db_object' ) );
		add_action( 'switch_to_blog', array( $this, 'add_table_to_db_object' ) );

		// Check if DB needs upgrading
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	/**
	 * Administration area hooks
	 *
	 * @since 0.1.0
	 */
	public function admin_init() {
		$this->maybe_upgrade_database();
	}

	/**
	 * Modify the database object and add the table to it
	 *
	 * This is necessary to do directly because WordPress does have a mechanism
	 * for manipulating them safely. It's pretty fragile, but oh well.
	 *
	 * @since 0.1.0
	 */
	public function add_table_to_db_object() {
		$this->db->blog_aliases       = "{$this->db->base_prefix}blog_aliases";
		$this->db->ms_global_tables[] = "blog_aliases";
	}

	/**
	 * Install this plugin on a specific site
	 *
	 * @since 0.1.0
	 *
	 * @param int $site_id
	 */
	public function install() {
		$this->upgrade_database();
	}

	/**
	 * Activation hook
	 *
	 * Handles both single & multi site installations
	 *
	 * @since 0.1.0
	 *
	 * @param   bool    $network_wide
	 */
	public function activate() {
		$this->install();
	}

	/**
	 * Should a database update occur
	 *
	 * Runs on `admin_init`
	 *
	 * @since 0.1.0
	 */
	private function maybe_upgrade_database() {

		// Check DB for version
		$db_version = get_network_option( -1, $this->db_version_key );

		// Needs
		if ( (int) $db_version < $this->db_version ) {
			$this->upgrade_database( $db_version );
		}
	}

	/**
	 * Create the database table
	 *
	 * @since 0.1.0
	 *
	 * @param  int $old_version
	 */
	private function upgrade_database( $old_version = 0 ) {

		// The main column alter
		if ( version_compare( (int) $old_version, $this->db_version, '>=' ) ) {
			return;
		}

		// Create term table
		$this->create_table();

		// Update the DB version
		update_network_option( -1, $this->db_version_key, $this->db_version );
	}

	/**
	 * Create the table
	 *
	 * @since 0.1.0
	 */
	private function create_table() {

		$charset_collate = '';
		if ( ! empty( $this->db->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$this->db->charset}";
		}

		if ( ! empty( $this->db->collate ) ) {
			$charset_collate .= " COLLATE {$this->db->collate}";
		}

		// Check for `dbDelta`
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$max_index_length = 191;

		dbDelta( array(
			"CREATE TABLE {$this->db->blog_aliases} (
				id bigint(20) NOT NULL auto_increment,
				blog_id bigint(20) NOT NULL,
				domain varchar(255) NOT NULL,
				created datetime NOT NULL default '0000-00-00 00:00:00',
				status varchar(20) NOT NULL default 'active',
				PRIMARY KEY (id),
				KEY blog_id (blog_id,domain(50),status),
				KEY domain (domain({$max_index_length}))
			) {$charset_collate};"
		) );

		// Make doubly sure the global database object is modified
		$this->add_table_to_db_object();
	}

	/**
	 * Delete all alias for a given site ID
	 *
	 * This bit is largely taken from `wp_delete_post()` as there is no meta-
	 * data function specifically designed to facilitate the deletion of all
	 * meta associated with a given object.
	 *
	 * @since 0.1.0
	 *
	 * @param  int    $site_id Site ID
	 */
	public function delete_all_aliases_for_site( $site_id = 0 ) {

		// Make doubly sure global database object is prepared
		$this->add_table_to_db_object();

		// Query the DB for metad ID's to delete
		$query     = "SELECT id FROM {$this->db->blog_aliases} WHERE blog_id = %d";
		$prepared  = $this->db->prepare( $query, $site_id );
		$alias_ids = $this->db->get_col( $prepared );

		// Bail if no site alias to delete
		if ( empty( $alias_ids ) ) {
			return;
		}

		// Loop through and delete all meta by ID
		foreach ( $alias_ids as $id ) {
			$result = (bool) $this->db->delete( $this->db->blog_aliases, array( $id => $site_id ) );

			// Clear the caches.
			wp_cache_delete($object_id, $meta_type . '_meta');
		}
	}
}

/**
 * Load the DB as early as possible, but after WordPress core is included
 *
 * @since 0.1.0
 */
function wp_site_aliases_db() {
	new WP_Site_Aliases_DB();
}
add_action( 'muplugins_loaded', 'wp_site_aliases_db', -PHP_INT_MAX );
