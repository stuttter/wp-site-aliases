<?php

/**
 * Site Aliases List Table
 *
 * @package Plugins/Site/Aliases/ListTable
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * List table for aliases
 */
final class WP_Site_Aliases_List_Table extends WP_List_Table {

	/**
	 * Prepare items for the list table
	 *
	 * @since 0.1.0
	 */
	public function prepare_items() {
		$this->items = array();

		if ( empty( $this->_args['site_id'] ) ) {
			return;
		}

		$id      = $this->_args['site_id'];
		$aliases = WP_Site_Alias::get_by_site( $id );

		if ( ! empty( $aliases ) && ! is_wp_error( $aliases ) ) {
			$this->items = $aliases;
		}
	}

	/**
	 * Get columns for the table
	 *
	 * @since 0.1.0
	 *
	 * @return array Map of column ID => title
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'domain'  => _x( 'Domain',  'wp-site-aliases' ),
			'status'  => _x( 'Status',  'wp-site-aliases' ),
			'created' => _x( 'Created', 'wp-site-aliases' )
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return apply_filters( 'wp_site_aliases_bulk_actions', array(
			'activate'   => esc_html__( 'Activate',   'wp-site-aliases' ),
			'deactivate' => esc_html__( 'Deactivate', 'wp-site-aliases' ),
			'delete'     => esc_html__( 'Delete',     'wp-site-aliases' )
		) );
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backwards-compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			/**
			 * Filter the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, $this->screen->id, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
			echo '<input type="hidden" name="site_id" value="' . esc_attr( $this->_args['site_id'] ) . '" />';
			wp_nonce_field( "site_aliases-bulk-{$this->_args['site_id']}" );
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . __( 'Select bulk action' ) . "</label>";
		echo "<select name='bulk_action$two' id='bulk-action-selector-" . esc_attr( $which ) . "'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

			echo "\t<option value='{$name}'{$class}>{$title}</option>\n";
		}

		echo "</select>\n";
		submit_button( __( 'Apply' ), 'action', false, false, array( 'id' => "doaction{$two}" ) );
		echo "\n";
	}

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @since 0.1.0
	 *
	 * @return string|bool The action name or False if no action was selected
	 */
	public function current_action() {

		if ( isset( $_REQUEST['bulk_action'] ) && -1 != $_REQUEST['bulk_action'] ) {
			return $_REQUEST['bulk_action'];
		}

		if ( isset( $_REQUEST['bulk_action2'] ) && -1 != $_REQUEST['bulk_action2'] ) {
			return $_REQUEST['bulk_action2'];
		}

		return false;
	}

	/**
	 * Get cell value for the checkbox column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_Site_Alias $alias Current alias item
	 * @return string HTML for the cell
	 */
	protected function column_cb( $alias ) {
		$alias_id = $alias->get_id();
		$domain   = $alias->get_domain();

		return '<label class="screen-reader-text" for="cb-select-' . esc_attr( $alias_id ) . '">'
			. sprintf( __( 'Select %s' ), esc_html( $domain ) ) . '</label>'
			. '<input type="checkbox" name="aliases[]" value="' . esc_attr( $alias_id )
			. '" id="cb-select-' . esc_attr( $alias_id ) . '" />';
	}

	/**
	 * Get cell value for the domain column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_Site_Alias $alias Current alias item
	 * @return string HTML for the cell
	 */
	protected function column_domain( $alias ) {

		// Default empty actions
		$actions = array();

		// Strip www.
		$domain   = $alias->get_domain();
		$status   = $alias->get_status();
		$site_id  = $alias->get_site_id();
		$alias_id = $alias->get_id();

		// Edit
		$edit_link = wp_site_aliases_admin_url( array(
			'page'    => 'site_alias_edit',
			'id'      => $site_id,
			'aliases' => $alias_id,
		) );

		// Active/Deactive
		if ( 'active' === $status ) {
			$text   = __( 'Deactivate', 'wp-site-aliases' );
			$action = 'deactivate';
		} else {
			$text   = __( 'Activate', 'wp-site-aliases' );
			$action = 'activate';
		}

		// Default args
		$args = array(
			'page'     => 'site_aliases',
			'action'   => $action,
			'id'       => $site_id,
			'aliases'  => $alias_id,
			'_wpnonce' => wp_create_nonce( "site_aliases-bulk-{$this->_args['site_id']}" )
		);

		$status_link = wp_site_aliases_admin_url( $args );

		// Delete
		$delete_args           = $args;
		$delete_args['action'] = 'delete';
		$delete_link           = wp_site_aliases_admin_url( $delete_args );

		// Edit
		if ( current_user_can( 'edit_alias', $alias_id ) ) {
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), esc_html__( 'Edit', 'wp-site-aliases' ) );
		}

		// Activate/deactivate
		if ( current_user_can( "{$action}_alias", $alias_id ) ) {
			$actions[ $action ] = sprintf( '<a href="%s">%s</a>', esc_url( $status_link ), esc_html( $text ) );
		}

		// Delete
		if ( current_user_can( 'delete_alias', $alias_id ) ) {
			$actions['delete'] = sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'wp-site-aliases' ) );
		}

		// Get HTML from actions
		$action_html = $this->row_actions( $actions, false );

		return '<strong>' . esc_html( $domain ) . '</strong>' . $action_html;
	}

	/**
	 * Get value for the status column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_Site_Alias $alias Current alias item
	 * @return string HTML for the cell
	 */
	protected function column_status( $alias ) {
		return ( 'active' === $alias->get_status() )
			? esc_html__( 'Active',   'wp-site-aliases' )
			: esc_html__( 'Inactive', 'wp-site-aliases' );
	}

	/**
	 * Get value for the status column
	 *
	 * @since 0.1.0
	 * @access protected
	 *
	 * @param WP_Site_Alias $alias Current alias item
	 *
	 * @return string HTML for the cell
	 */
	protected function column_created( $alias ) {
		return mysql2date( get_option( 'date_format' ), $alias->get_created() ) . '<br>' .
			   mysql2date( get_option( 'time_format' ), $alias->get_created() );
	}
}
