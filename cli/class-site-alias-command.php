<?php

namespace WP_Site_Alias\CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_Error;

class Alias_Command extends WP_CLI_Command {

	/**
	 * Display a list of aliases
	 *
	 * @param Alias[] $aliases Alias objects to show
	 * @param array $options
	 */
	protected function display( $aliases, $options ) {

		$options = wp_parse_args( $options, array(
			'format' => 'table',
			'fields' => array( 'id', 'domain', 'site', 'created', 'status' ),
		) );

		$mapper = function ( WP_Site_Alias $alias ) {
			return array(
				'id'      => $alias->get_id(),
				'domain'  => $alias->get_domain(),
				'site'    => $alias->get_site_id(),
				'created' => $alias->get_created(),
				'status'  => ( 'active' === $alias->get_status() )
					? __( 'Active',   'wp-site-aliases' )
					: __( 'Inactive', 'wp-site-aliases' )
			);
		};

		$display_items = Utils\iterator_map( $aliases, $mapper );

		$formatter = new Formatter( $options );

		$formatter->display_items( $display_items );
	}

	/**
	 * ## OPTIONS
	 *
	 * [<site>]
	 * : Site ID (defaults to current site, use `--url=...`)
	 *
	 * [--format=<format>]
	 * : Format to display as (table, json, csv, count)
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$id = empty( $args[0] )
			? get_current_blog_id()
			: absint( $args[0] );

		$aliases = WP_Site_Alias::get_by_site( $id );

		if ( empty( $aliases ) ) {
			return;
		}

		$this->display( $aliases, $assoc_args );
	}

	/**
	 * Get a single alias
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Alias ID
	 *
	 * [--format=<format>]
	 * : Format to display as (table, json, csv, count)
	 */
	public function get( $args, $assoc_args ) {
		$alias = WP_Site_Alias::get_instance( $args[0] );

		if ( empty( $alias ) ) {
			$alias = new WP_Error( 'wp_site_aliases_cli_alias_not_found', __( 'Invalid alias ID', 'wp-site-aliases' ) );
		}

		if ( is_wp_error( $alias ) ) {
			return WP_CLI::error( $alias->get_error_message() );
		}

		$aliases = array( $alias );

		$this->display( $aliases, $assoc_args );
	}

	/**
	 * Delete a single alias
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Alias ID
	 */
	public function delete( $args ) {
		$alias = WP_Site_Alias::get_instance( $args[0] );

		if ( empty( $alias ) ) {
			$alias = new WP_Error( 'wp_site_aliases_cli_alias_not_found', __( 'Invalid alias ID', 'wp-site-aliases' ) );
		}

		if ( is_wp_error( $alias ) ) {
			return WP_CLI::error( $alias->get_error_message() );
		}

		$result = $alias->delete();

		if ( empty( $result ) || is_wp_error( $result ) ) {
			return WP_CLI::error( __( 'Could not delete alias', 'wp-site-aliases' ) );
		}
	}
}
