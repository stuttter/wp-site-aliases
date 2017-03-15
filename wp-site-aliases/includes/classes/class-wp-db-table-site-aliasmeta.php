<?php

/**
 * Site Alias Meta: WP_DB_Table_Site_Aliasmeta class
 *
 * @package Plugins/Sites/Aliases/Database/Meta
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "blog_aliasmeta" database table
 *
 * @since 1.0.0
 */
final class WP_DB_Table_Site_Aliasmeta extends WP_DB_Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'blog_aliasmeta';

	/**
	 * @var string Database version
	 */
	protected $version = 201703150001;

	/**
	 * @var boolean This is a global table
	 */
	protected $global = true;

	/**
	 * Setup the database schema
	 *
	 * @since 1.0.0
	 */
	protected function set_schema() {
		$max_index_length = 191;
		$this->schema     = "meta_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			blog_alias_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			KEY blog_alias_id (blog_alias_id),
			KEY meta_key (meta_key({$max_index_length}))";
	}

	/**
	 * Handle schema changes
	 *
	 * @since 1.0.0
	 */
	protected function upgrade() {

		// 1.0.0 to 2.0.0
		if ( version_compare( (int) $this->db_version, 201609100003, '<=' ) ) {
			$this->db->query( "ALTER TABLE {$this->table_name} CHANGE `id` `meta_id` BIGINT(20) NOT NULL AUTO_INCREMENT;" );
		}
	}
}
