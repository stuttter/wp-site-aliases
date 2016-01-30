<?php

namespace WP_Site_Alias_Network_Command\CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_Error;

class WP_Site_Alias_Network_Command extends WP_CLI_Command {
	/**
	 * Display a list of aliases
	 *
	 * @param WP_Site_Alias_Network[] $aliases Alias objects to show
	 * @param array $options
	 */
	protected function display( $aliases, $options ) {
		$defaults = array(
			'format' => 'table',
			'fields' => array( 'id', 'domain', 'network', 'active' ),
		);
		$options = wp_parse_args( $options, $defaults );

		$mapper = function ( WP_Site_Alias_Network $alias ) {
			return array(
				'id'      => (int) $alias->get_id(),
				'domain'  => $alias->get_domain(),
				'network' => (int) $alias->get_network_id(),
				'active'  => $alias->is_active() ? __( 'Active', 'wp-site-aliases' ) : __( 'Inactive', 'wp-site-aliases' ),
			);
		};
		$display_items = Utils\iterator_map( $aliases, $mapper );

		$formatter = new Formatter( $options );
		$formatter->display_items( $display_items );
	}

	/**
	 * ## OPTIONS
	 *
	 * [<network>]
	 * : Network ID (defaults to current network, use `--url=...`)
	 *
	 * [--format=<format>]
	 * : Format to display as (table, json, csv, count)
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$id = empty( $args[0] ) ? get_current_site()->id : absint( $args[0] );

		$aliases = WP_Site_Alias_Network::get_by_network( $id );

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
		$alias = WP_Site_Alias_Network::get( $args[0] );

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
		$alias = WP_Site_Alias_Network::get( $args[0] );

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
