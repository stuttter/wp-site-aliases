<?php

/**
 * Site Aliases Database: WP_DB_Table_Site_Aliases class
 *
 * @package Plugins/Sites/Aliases/Database/Object
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the global "blog_aliases" database table
 *
 * @since 5.0.0
 */
final class WP_DB_Table_Site_Aliases extends WP_DB_Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'blog_aliases';

	/**
	 * @var string Database version
	 */
	protected $version = 201704110001;

	/**
	 * @var boolean This is a global table
	 */
	protected $global = true;

	/**
	 * Setup the database schema
	 *
	 * @since 5.0.0
	 */
	protected function set_schema() {
		$max_index_length = 191;
		$this->schema     = "id bigint(20) unsigned NOT NULL auto_increment,
			blog_id bigint(20) unsigned NOT NULL default '0',
			domain varchar(255) NOT NULL,
			created datetime NOT NULL default '0000-00-00 00:00:00',
			status varchar(20) NOT NULL default 'active',
			type varchar(20) NOT NULL default 'mask',
			PRIMARY KEY (id),
			KEY blog_id (blog_id,domain(50),status,type),
			KEY domain (domain({$max_index_length}))";
	}

	/**
	 * Handle schema changes
	 *
	 * @since 5.0.0
	 */
	protected function upgrade() {

		// 6.0.0 to 7.0.0
		if ( version_compare( (int) $this->db_version, 201704110001, '<=' ) ) {
			$this->db->query( "ALTER TABLE {$this->table_name} MODIFY `id` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT;" );
			$this->db->query( "ALTER TABLE {$this->table_name} MODIFY `blog_id` BIGINT(20) unsigned NOT NULL default 0;" );
		}
	}
}
