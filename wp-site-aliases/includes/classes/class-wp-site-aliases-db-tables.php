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
 * @since 1.0.0
 */
final class WP_Site_Aliases_DB {

	/**
	 * @var string Plugin version
	 */
	public $version = '1.0.0';

	/**
	 * @var string Database version
	 */
	public $db_version = 201608310006;

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
	 * @since 1.0.0
	 */
	public function __construct() {

		// Activation hook
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Setup plugin
		$this->db = $GLOBALS['wpdb'];

		// Force table on to the global database object
		add_action( 'init',           array( $this, 'add_tables_to_db_object' ) );
		add_action( 'switch_to_blog', array( $this, 'add_tables_to_db_object' ) );

		// Check if DB needs upgrading
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	/**
	 * Modify the database object and add the table to it
	 *
	 * This is necessary to do directly because WordPress does have a mechanism
	 * for manipulating them safely. It's pretty fragile, but oh well.
	 *
	 * @since 1.0.0
	 */
	public function add_tables_to_db_object() {
		if ( ! isset( $this->db->blog_aliases ) ) {
			$this->db->blog_aliases       = "{$this->db->base_prefix}blog_aliases";
			$this->db->blog_aliasmeta     = "{$this->db->base_prefix}blog_aliasmeta";
			$this->db->ms_global_tables[] = 'blog_aliases';
			$this->db->ms_global_tables[] = 'blog_aliasmeta';
		}
	}

	/**
	 * Administration area hooks
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {
		$this->upgrade_database();
	}

	/**
	 * Activation hook
	 *
	 * Handles both single & multi site installations
	 *
	 * @since 1.0.0
	 *
	 * @param   bool    $network_wide
	 */
	public function activate() {
		$this->upgrade_database();
	}

	/**
	 * Create the database table
	 *
	 * @since 1.0.0
	 *
	 * @param  int $old_version
	 */
	private function upgrade_database( $old_version = 0 ) {

		// Get current version
		$old_version = get_network_option( -1, $this->db_version_key );

		// Bail if no upgrade needed
		if ( version_compare( (int) $old_version, $this->db_version, '>=' ) ) {
			return;
		}

		// Check for `dbDelta`
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Bail if upgrading global tables is not allowed
		if ( ! wp_should_upgrade_global_tables() ) {
			return;
		}

		// Create term tables
		$this->create_tables();

		// Update the DB version
		update_network_option( -1, $this->db_version_key, $this->db_version );
	}

	/**
	 * Create the table
	 *
	 * @since 1.0.0
	 */
	private function create_tables() {
		$this->add_tables_to_db_object();

		$charset_collate = '';
		if ( ! empty( $this->db->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$this->db->charset}";
		}

		if ( ! empty( $this->db->collate ) ) {
			$charset_collate .= " COLLATE {$this->db->collate}";
		}

		$sql = array();
		$max_index_length = 191;

		// Aliases
		$sql[] =  "CREATE TABLE {$this->db->blog_aliases} (
			id bigint(20) NOT NULL auto_increment,
			blog_id bigint(20) NOT NULL,
			domain varchar(255) NOT NULL,
			created datetime NOT NULL default '0000-00-00 00:00:00',
			status varchar(20) NOT NULL default 'active',
			PRIMARY KEY (id),
			KEY blog_id (blog_id,domain(50),status),
			KEY domain (domain({$max_index_length}))
		) {$charset_collate};";

		// Relationship meta
		$sql[] = "CREATE TABLE {$this->db->blog_aliasmeta} (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			blog_alias_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			KEY blog_alias_id (blog_alias_id),
			KEY meta_key (meta_key({$max_index_length}))
		) {$charset_collate};";

		dbDelta( $sql );
	}
}

/**
 * Load the DB as early as possible, but after WordPress core is included
 *
 * @since 1.0.0
 */
function wp_site_aliases_db() {
	new WP_Site_Aliases_DB();
}
add_action( 'muplugins_loaded', 'wp_site_aliases_db', -PHP_INT_MAX );
