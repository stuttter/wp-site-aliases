<?php

/**
 * Site Aliases Admin
 *
 * @package Plugins/Site/Aliases/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add menus in network and site dashboards
 *
 * @since 0.1.0
 */
function wp_site_aliases_add_menu_item() {

	// Define empty array
	$hooks = array();

	// Network admin page
	if ( is_network_admin() ) {
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_site_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'edit_site_aliases',   'site_alias_edit', 'wp_site_aliases_output_edit_page' );
		remove_submenu_page( 'sites.php', 'site_aliases'    );
		remove_submenu_page( 'sites.php', 'site_alias_edit' );

		// Network management of all aliases
		$hooks[] = add_menu_page( esc_html__( 'All Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_network_options', 'site_aliases_all', 'wp_site_aliases_output_list_page', 'dashicons-randomize', 6 );

	// Blog admin page
	} elseif ( is_blog_admin() ) {
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'edit_aliases',   'site_alias_edit', 'wp_site_aliases_output_edit_page' );
		remove_submenu_page( 'index.php', 'site_alias_edit' );
	}

	// Load the list table
	foreach ( $hooks as $hook ) {
		add_action( "load-{$hook}", 'wp_site_aliases_handle_site_actions'       );
		add_action( "load-{$hook}", 'wp_site_aliases_load_site_list_table'      );
		add_action( "load-{$hook}", 'wp_site_aliases_fix_hidden_menu_highlight' );
	}
}

function wp_site_aliases_get_admin_action() {

	$action = false;

	// Regular action
	if ( ! empty( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];

	// Bulk action (top)
	} elseif ( ! empty( $_REQUEST['bulk_action'] ) ) {
		$action = $_REQUEST['bulk_action'];

	// Bulk action (bottom)
	} elseif ( ! empty( $_REQUEST['bulk_action2'] ) ) {
		$action = $_REQUEST['bulk_action2'];
	}

	return $action;
}

/**
 * Output UI for viewing all aliases
 *
 * @since 2.0.0
 */
function wp_site_aliases_output_all_aliases() {

}

/**
 * Output UI for adding a new alias to a site
 *
 * @since 2.0.0
 */
function wp_site_aliases_output_add_new_alias() {

}

/**
 * Load the list table and populate some essentials
 *
 * @since 0.1.0
 */
function wp_site_aliases_load_site_list_table() {
	global $wp_list_table;

	// Include the list table class
	require_once dirname( __FILE__ ) . '/class-wp-site-aliases-list-table.php';

	// Get site ID being requested
	$site_id = wp_site_aliases_get_site_id();

	// Create a new list table object
	$wp_list_table = new WP_Site_Aliases_List_Table( array(
		'site_id' => $site_id
	) );

	$wp_list_table->prepare_items( $site_id );
}

/**
 * Override network files, to correct main submenu navigation highlighting
 *
 * @since 0.1.0
 *
 * @global string $parent_file
 * @global string $submenu_file
 */
function wp_site_aliases_fix_hidden_menu_highlight() {
	global $parent_file, $submenu_file;

	if ( is_network_admin() ) {
		$parent_file  = 'sites.php';
		$submenu_file = 'sites.php';
	} elseif ( is_blog_admin() ) {
		$parent_file  = 'index.php';
		$submenu_file = 'site_aliases';
	}
}

/**
 * Add site list column to list
 *
 * @since 0.1.0
 *
 * @param   array  $columns  Column map of ID => title
 *
 * @return  array
 */
function wp_site_aliases_add_site_list_column( $columns ) {
	$columns['alias_ids'] = esc_html__( 'Aliases', 'wp-site-aliases' );
	return $columns;
}

/**
 * Output the site list column
 *
 * @since 0.1.0
 *
 * @param  string  $column   Column ID
 * @param  int     $site_id  Site ID
 */
function wp_site_aliases_output_site_list_column( $column, $site_id ) {

	// Bail if not for aliases column
	if ( 'alias_ids' !== $column ) {
		return;
	}

	// Get aliases
	$aliases = WP_Site_Alias::get_by_site( $site_id );

	// Show all aliases
	if ( ! empty( $aliases ) ) {
		foreach ( $aliases as $alias ) {
			echo esc_html( $alias->get_domain() ) . '<br>';
		}

	// No aliases
	} else {
		esc_html_e( '&mdash;', 'wp-site-aliases' );
	}
}

/**
 * Add tab to end of tabs array
 *
 * @since 0.1.0
 *
 * @param array $tabs
 * @return array
 */
function wp_site_aliases_add_site_tab( $tabs = array() ) {

	// "Aliases" tab
	$tabs['site-aliases'] = array(
		'label' => esc_html__( 'Aliases', 'wp-site-aliases' ),
		'url'   => add_query_arg( array( 'page' => 'site_aliases' ), 'sites.php' ),
		'cap'   => 'manage_site_aliases'
	);

	// Return tabs
	return $tabs;
}

/**
 * Output the admin page header
 *
 * @since 0.1.0
 *
 * @param  int  $site_id  Site ID
 */
function wp_site_aliases_output_page_header( $site_id = 0 ) {
	global $title;

	// Network
	if ( is_network_admin() && ! wp_site_aliases_is_network_aliases() ) :

		// Header
		$title = sprintf( esc_html__( 'Edit Site: %s' ), get_blog_option( $site_id, 'blogname' ) );

		// This is copied from WordPress core (sic)
		?><div class="wrap">
			<h1 id="edit-site"><?php echo $title; ?></h1>
			<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $site_id, '/' ) ); ?>"><?php esc_html_e( 'Visit', 'wp-site-aliases' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $site_id ) ); ?>"><?php esc_html_e( 'Dashboard', 'wp-site-aliases' ); ?></a></p><?php

			// Admin notices
			do_action( 'wp_site_aliases_admin_notices' );

			// Tabs in network admin
			network_edit_site_nav( array(
				'blog_id'  => $site_id,
				'selected' => 'site-aliases'
			) );

	// Site
	else :
		?><div class="wrap">
			<h1 id="edit-site"><?php esc_html_e( 'Site Aliases', 'wp-site-aliases' ); ?></h1><?php

		// Admin notices
		do_action( 'wp_site_aliases_admin_notices' );
	endif;
}

/**
 * Close the .wrap div
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_page_footer() {
	?></div><?php
}

/**
 * Handle submission of the list page
 *
 * Handles bulk actions for the list page. Redirects back to itself after
 * processing, and exits.
 *
 * @since 0.1.0
 *
 * @param  string  $action  Action to perform
 */
function wp_site_aliases_handle_site_actions() {

	// Look for actions
	$action = wp_site_aliases_get_admin_action();

	// Bail if no action
	if ( false === $action ) {
		return;
	}

	// Get action
	$action      = sanitize_key( $action );
	$site_id     = wp_site_aliases_get_site_id();
	$redirect_to = remove_query_arg( array( 'did_action', 'processed', 'alias_ids', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_site_aliases_admin_url();
	}

	// Get aliases being bulk actioned
	$processed = array();
	$alias_ids = ! empty( $_REQUEST['alias_ids'] )
		? array_map( 'absint', (array) $_REQUEST['alias_ids'] )
		: array();

	// Redirect args
	$args = array(
		'page'       => wp_site_aliases_is_network_aliases() ? 'site_aliases_all' : 'site_aliases',
		'id'         => $site_id,
		'did_action' => $action,
	);

	// What's the action?
	switch ( $action ) {

		// Bulk activate
		case 'activate':
			foreach ( $alias_ids as $alias_id ) {
				$alias = WP_Site_Alias::get_instance( $alias_id );

				// Skip erroneous aliases
				if ( is_wp_error( $alias ) ) {
					$args['did_action'] = $alias->get_error_code();
					continue;
				}

				// Process switch
				if ( $alias->set_status( 'active' ) ) {
					$processed[] = $alias_id;
				}
			}
			break;

		// Bulk deactivate
		case 'deactivate':
			foreach ( $alias_ids as $alias_id ) {
				$alias = WP_Site_Alias::get_instance( $alias_id );

				// Skip erroneous aliases
				if ( is_wp_error( $alias ) ) {
					$args['did_action'] = $alias->get_error_code();
					continue;
				}

				// Process switch
				if ( $alias->set_status( 'inactive' ) ) {
					$processed[] = $alias_id;
				}
			}
			break;

		// Single/Bulk Delete
		case 'delete':
			$args['domains'] = array();

			foreach ( $alias_ids as $alias_id ) {
				$alias = WP_Site_Alias::get_instance( $alias_id );

				// Skip erroneous aliases
				if ( is_wp_error( $alias ) ) {
					$args['did_action'] = $alias->get_error_code();
					continue;
				}

				// Aliases don't exist after we delete them, so pass the
				// domain for messages and such
				if ( $alias->delete() ) {
					$args['domains'][] = $alias->get_domain();
					$processed[] = $alias_id;
				}
			}

			break;

		// Single Add
		case 'add' :
			check_admin_referer( "site_alias_add-{$site_id}" );

			// Check that the parameters are correct first
			$params = wp_site_aliases_validate_alias_parameters( wp_unslash( $_POST ) );

			// Error
			if ( is_wp_error( $params ) ) {
				$args['did_action'] = $params->get_error_code();
				continue;
			}

			// Add
			$alias = WP_Site_Alias::create(
				$params['site_id'],
				$params['domain'],
				$params['status']
			);

			// Bail if an error occurred
			if ( is_wp_error( $alias ) ) {
				$args['did_action'] = $alias->get_error_code();
				continue;
			}

			$processed[] = $alias->get_id();

			break;

		// Single Edit
		case 'edit' :
			check_admin_referer( "site_alias_edit-{$site_id}" );

			// Check that the parameters are correct first
			$params = wp_site_aliases_validate_alias_parameters( wp_unslash( $_POST ) );

			// Error messages
			if ( is_wp_error( $params ) ) {
				$args['did_action'] = $params->get_error_code();
				continue;
			}

			$alias_id = $alias_ids[0];
			$alias    = WP_Site_Alias::get_instance( $alias_id );

			// Error messages
			if ( is_wp_error( $alias ) ) {
				$args['did_action'] = $alias->get_error_code();
				continue;
			}

			// Update
			$result = $alias->update( $params );

			// Error messages
			if ( is_wp_error( $result ) ) {
				$args['did_action'] = $result->get_error_code();
				continue;
			}

			$processed[] = $alias_id;

			break;

		// Any other bingos
		default:
			check_admin_referer( "site_aliases-bulk-{$site_id}" );
			do_action_ref_array( "aliases_bulk_action-{$action}", array( $alias_ids, &$processed, $action ) );

			break;
	}

	// Add processed aliases to redirection
	$args['processed'] = $processed;
	$redirect_to = add_query_arg( $args, $redirect_to );

	// Redirect
	wp_safe_redirect( $redirect_to );
	exit();
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_edit_page() {

	// Get site ID
	$site_id = wp_site_aliases_get_site_id();

	// Edit
	if ( ! empty( $_REQUEST['alias_ids'] ) ) {
		$alias_id = absint( $_REQUEST['alias_ids'] );
		$alias    = WP_Site_Alias::get_instance( $alias_id );
		$action   = 'edit';

	// Add
	} else {
		$alias_id = 0;
		$alias    = null;
		$action   = 'add';
	}

	// URL
	$action_url = wp_site_aliases_admin_url( array(
		'action' => $action
	) );

	// Add
	if ( empty( $alias ) || ! empty( $_POST['_wpnonce'] ) ) {
		$active = ! empty( $_POST['active'] );
		$domain = ! empty( $_POST['domain'] )
			? wp_unslash( $_POST['domain'] )
			: '';

	// Edit
	} else {
		$active = ( 'active' === $alias->get_status() );
		$domain = $alias->get_domain();
	}

	// Output the header, maybe with network site tabs
	wp_site_aliases_output_page_header( $site_id );

	?><form method="post" action="<?php echo esc_url( $action_url ); ?>">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="blog_alias"><?php echo esc_html_x( 'Domain Name', 'field name', 'wp-site-aliases' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text code" name="domain" id="blog_alias" value="<?php echo esc_attr( $domain ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php echo esc_html_x( 'Status', 'field name', 'wp-site-aliases' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="status" <?php checked( $active ); ?>>

						<?php esc_html_e( 'Active', 'wp-site-aliases' ); ?>
					</label>
				</td>
			</tr><?php

			// Site picker for network admin
			if ( is_network_admin() && wp_site_aliases_is_network_aliases() ) : 

				// Get all of the sites - OY
				$sites = wp_site_aliases_get_sites();

				?><tr>
					<th scope="row">
						<?php echo esc_html_x( 'Site', 'field name', 'wp-site-aliases' ); ?>
					</th>
					<td>
						<label>
							<select name="site_id" id="site_id"><?php

							// Loop throug sites
							foreach ( $sites as $site ) :

								// Loop through sites
								?><option value="<?php echo esc_attr( $site->blog_id ); ?>"><?php echo esc_html( $site->domain . $site->path ); ?></option><?php

							endforeach;
								
							?></select>
						</label>
					</td>
				</tr><?php
			endif;

		?></table>

		<input type="hidden" name="action"    value="<?php echo esc_attr( $action   ); ?>">

		<?php

		// Hidden site ID for blog admin
		if ( ! wp_site_aliases_is_network_aliases() ) :

			?><input type="hidden" name="site_id"   value="<?php echo esc_attr( $site_id  ); ?>"><?php

		endif;

		?><input type="hidden" name="alias_ids" value="<?php echo esc_attr( $alias_id ); ?>"><?php

		// Add
		if ( 'add' === $action ) {
			wp_nonce_field( "site_alias_add-{$site_id}" );
			$submit_text = esc_html__( 'Add Alias', 'wp-site-aliases' );

		// Edit
		} else {
			wp_nonce_field( "site_alias_edit-{$site_id}" );
			$submit_text = esc_html__( 'Save Alias', 'wp-site-aliases' );
		}

		// Submit button
		submit_button( $submit_text );

	?></form><?php

	// Footer
	wp_site_aliases_output_page_footer();
}


/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_list_page() {
	global $wp_list_table;

	// Get site ID being requested
	$site_id = wp_site_aliases_get_site_id();

	// Action URLs
	$form_url = $action_url = wp_site_aliases_admin_url();

	// Output header, maybe with tabs
	wp_site_aliases_output_page_header( $site_id ); ?>

	<div id="col-container" style="margin-top: 20px;">
		<div id="col-right">
			<div class="col-wrap">
				<div class="form-wrap">
					<form method="post" action="<?php echo esc_url( $form_url ); ?>">
						<?php $wp_list_table->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h2><?php esc_html_e( 'Add New Alias', 'wp-site-aliases' ); ?></h2>
					<form method="post" action="<?php echo esc_url( $action_url ); ?>">
						<div class="form-field form-required domain-wrap">
							<label for="blog_alias"><?php echo esc_html_x( 'Domain Name', 'field name', 'wp-site-aliases' ); ?></label>
							<input type="text" class="regular-text code" name="domain" id="blog_alias" value="">
							<p><?php esc_html_e( 'The fully qualified domain name that this site should load for.', 'wp-site-aliases' ); ?></p>
						</div><?php

							// Site picker for network admin
							if ( is_network_admin() && wp_site_aliases_is_network_aliases() ) : 

								// Get all of the sites - OY
								$sites = wp_site_aliases_get_sites();

								// Output the site ID field
								?><div>
									<label for="site_id"><?php echo esc_html_x( 'Site', 'field name', 'wp-site-aliases' ); ?></label>
									<select name="site_id" id="site_id"><?php

									// Loop throug sites
									foreach ( $sites as $site ) :

										// Loop through sites
										?><option value="<?php echo esc_attr( $site->blog_id ); ?>" <?php selected( $site->blog_id, $site_id ); ?>><?php echo esc_html( $site->domain . $site->path ); ?></option><?php

									endforeach;

									?></select>
								</div><?php
							endif;

						?><div class="form-field form-required status-wrap">
							<label for="status"><?php echo esc_html_x( 'Status', 'field name', 'wp-site-aliases' ); ?></label>
							<label>
								<input type="checkbox" name="status" <?php checked( true ); ?> />

								<?php esc_html_e( 'Active', 'wp-site-aliases' ); ?>
							</label>
							<p><?php esc_html_e( 'Whether this domain is ready to accept incoming requests.', 'wp-site-aliases' ); ?></p>
						</div>

						<input type="hidden" name="action"  value="add"><?php

						// 
						if ( ! wp_site_aliases_is_network_aliases() ) : 

							?><input type="hidden" name="site_id" value="<?php echo esc_attr( $site_id ); ?>"><?php

						endif;

						wp_nonce_field( "site_alias_add-{$site_id}" );

						submit_button( esc_html__( 'Add New Alias', 'wp-site-aliases' ) );

					?></form>
				</div>
			</div>
		</div>
	</div><?php

	// Footer
	wp_site_aliases_output_page_footer();
}

/**
 * Output admin notices
 *
 * @since 0.1.0
 *
 * @global type $wp_list_table
 */
function wp_site_aliases_output_admin_notices() {

	// Add messages for bulk actions
	if ( empty( $_REQUEST['did_action'] ) ) {
		return;
	}

	// Vars
	$did_action = sanitize_key( $_REQUEST['did_action'] );
	$processed  = ! empty( $_REQUEST['processed'] ) ? wp_parse_id_list( (array) $_REQUEST['processed'] ) : array();
	$processed  = array_map( 'absint', $processed );
	$count      = count( $processed );
	$output     = array();
	$messages   = array(

		// Success messages
		'activate'   => _n( '%s alias activated.',   '%s aliases activated.',   $count, 'wp-site-aliases' ),
		'deactivate' => _n( '%s alias deactivated.', '%s aliases deactivated.', $count, 'wp-site-aliases' ),
		'delete'     => _n( '%s alias deleted.',     '%s aliases deleted.',     $count, 'wp-site-aliases' ),
		'add'        => _n( '%s alias added.',       '%s aliases added.',       $count, 'wp-site-aliases' ),
		'edit'       => _n( '%s alias updated.',     '%s aliases updated.',     $count, 'wp-site-aliases' ),

		// Failure messages
		'wp_site_aliases_alias_domain_exists'  => __( 'That domain is already registered.',  'wp-site-aliases' ),
		'wp_site_aliases_alias_update_failed'  => __( 'Update failed.',                      'wp-site-aliases' ),
		'wp_site_aliases_alias_delete_failed'  => __( 'Delete failed.',                      'wp-site-aliases' ),
		'wp_site_aliases_alias_invalid_id'     => __( 'Invalid site ID.',                    'wp-site-aliases' ),
		'wp_site_aliases_no_domain'            => __( 'Missing domain.',                     'wp-site-aliases' ),
		'wp_site_aliases_domain_invalid_chars' => __( 'Domain contains invalid characters.', 'wp-site-aliases' ),
		'wp_site_aliases_invalid_site'         => __( 'Invalid site ID.',                    'wp-site-aliases' ),
	);

	// Insert the placeholder
	if ( ! empty( $messages[ $did_action ] ) ) {
		$output[] = sprintf( $messages[ $did_action ], number_format_i18n( $count ) );
	}

	// Bail if no messages
	if ( empty( $output ) ) {
		return;
	}

	// Get success keys
	$success = array_keys( array_slice( $messages, 0, 5 ) );

	// Which class
	$notice_class = in_array( $did_action, $success )
		? 'notice-success'
		: 'notice-warning';

	// Start a buffer
	ob_start();

	?><div id="message" class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible">
		<p><?php echo implode( '</p><p>', $output ); ?></p>
	</div><?php

	// Output the buffer
	ob_end_flush();
}
