<?php

/**
 * Site Aliases Admin
 *
 * @package Plugins/Site/Aliases/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return the site ID being modified
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_site_aliases_get_site_id() {

	// Set the default
	$default_id = is_blog_admin()
		? get_current_blog_id()
		: 0;

	// Get site ID being requested
	$site_id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: $default_id;

	// No site ID
	if ( empty( $site_id ) ) {
		wp_die( esc_html__( 'Invalid site ID.', 'wp-site-aliases' ) );
	}

	// Get the blog details
	$details = get_blog_details( $site_id );

	// No blog details
	if ( empty( $details ) ) {
		wp_die( esc_html__( 'Invalid site ID.', 'wp-site-aliases' ) );
	}

	// Return the blog ID
	return (int) $details->blog_id;
}

function wp_site_aliases_add_menu_item() {

	// Define empty array
	$hooks = array();

	// Network admin page
	if ( is_network_admin() ) {
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_alias_edit', 'wp_site_aliases_output_edit_page' );
		remove_submenu_page( 'sites.php', 'site_aliases'    );
		remove_submenu_page( 'sites.php', 'site_alias_edit' );

	// Blog admin page
	} elseif ( is_blog_admin() ) {
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_alias_edit', 'wp_site_aliases_output_edit_page' );
	}

	// Load the list table
	foreach ( $hooks as $hook ) {
		add_action( "load-{$hook}", 'wp_site_aliases_handle_actions'  );
		add_action( "load-{$hook}", 'wp_site_aliases_load_list_table' );
	}
}

/**
 * Load the list table and populate some essentials
 *
 * @since 0.1.0
 */
function wp_site_aliases_load_list_table() {
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

	// Correct menu highlighting
	wp_site_aliases_override_network_files();
}

/**
 * Override network files, to correct main submenu navigation highlighting
 *
 * @since 0.1.0
 *
 * @global string $parent_file
 * @global string $submenu_file
 */
function wp_site_aliases_override_network_files() {
	global $parent_file, $submenu_file;

	if ( is_network_admin() ) {
		$parent_file  = 'sites.php';
		$submenu_file = 'sites.php';
	}
}

/**
 * Enqueue admin scripts
 *
 * @since 0.1.0
 */
function wp_site_aliases_admin_enqueue_scripts() {

	// Set location & version for scripts & styles
	$src = wp_site_aliases_get_plugin_url();
	$ver = wp_site_aliases_get_asset_version();

	// Styles
	wp_enqueue_style( 'wp-site-aliases', $src . 'assets/css/site-aliases.css', array(), $ver );
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
	$columns['aliases'] = esc_html__( 'Aliases', 'wp-site-aliases' );
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
	if ( 'aliases' !== $column ) {
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
 * Output the site tab if we're on the right page
 *
 * @since 0.1.0
 *
 * Outputs the link, then moves it into place using JS, as there are no hooks to
 * speak of.
 */
function wp_site_aliases_maybe_output_site_tab() {

	// Bail for WordPress 4.6 - uses wp_site_aliases_add_site_tab()
	if ( function_exists( 'network_edit_site_tabs' ) ) {
		return;
	}

	// Bail if not in network admin
	if ( ! is_network_admin() ) {
		return;
	}

	// Bail if not looking at sites.php
	if ( $GLOBALS['parent_file'] !== 'sites.php' || $GLOBALS['submenu_file'] !== 'sites.php' ) {
		return;
	}

	// Bail if no ID
	$site_id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
	if ( empty( $site_id ) ) {
		return;
	}

	// Look for active tab
	$class  = ! empty( $_REQUEST['action'] ) && in_array( sanitize_key( $_REQUEST['action'] ), array( 'site_aliases', 'site_alias_edit', 'site_alias_add' ), true )
		? ' nav-tab-active'
		: ''; ?>

	<span id="wp-site-aliases-nav-link" class="hide-if-no-js">
		<a href="<?php echo network_admin_url( add_query_arg( array( 'page' => 'site_aliases', 'id' => $site_id ), 'sites.php' ) ); ?>" class="nav-tab<?php echo esc_attr( $class ); ?>"><?php esc_html_e( 'Aliases', 'wp-site-aliases' ) ?></a>
	</span>
	<script>jQuery( function wp_site_aliases( $ ) { $( '#wp-site-aliases-nav-link' ).appendTo( $( '.nav-tab-wrapper' ) ); } );</script>

<?php
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
		'label' => __( 'Aliases', 'wp-site-aliases' ),
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
 * @param  int    $site_id        Site ID
 */
function wp_site_aliases_output_page_header( $site_id ) {
	global $title;

	// Network
	if ( is_network_admin() ) :

		// Correct menu highlighting
		wp_site_aliases_override_network_files();

		// Header
		$details = get_blog_details( $site_id );
		$title   = sprintf( __( 'Edit Site: %s' ), esc_html( $details->blogname ) );

		?><div class="wrap">
			<h1 id="edit-site"><?php echo $title; ?></h1>
			<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $site_id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $site_id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p><?php

			// Admin notices
			do_action( 'wp_site_aliases_admin_notices' );

			// Tabs in network admin
			network_edit_site_tabs( array(
				'blog_id'  => $site_id,
				'selected' => 'site-aliases'
			) );

	// Site
	else :
		?><div class="wrap"><h1 id="edit-site"><?php esc_html_e( 'Site Aliases', 'wp-site-aliases' ); ?></h1><?php

		// Admin notices
		do_action( 'wp_site_aliases_admin_notices' );
	endif;
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
function wp_site_aliases_handle_actions() {

	// Bail if no action
	if ( empty( $_POST['action'] ) ) {
		return;
	}

	// Get action
	$action      = sanitize_key( $_POST['action'] );
	$site_id     = wp_site_aliases_get_site_id();
	$redirect_to = remove_query_arg( array( 'did_action', 'aliases', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_site_aliases_admin_url();
	}

	// Get aliases being bulk actioned
	$processed = array();
	$aliases   = empty( $_REQUEST['aliases'] )
		? array()
		: array_map( 'absint', (array) $_REQUEST['aliases'] );

	$args = array(
		'did_action' => $action,
		'aliases'    => join( ',', $aliases )
	);

	switch ( $action ) {

		// Bulk activate
		case 'activate':
			foreach ( $aliases as $alias_id ) {
				$alias = WP_Site_Alias::get( $alias_id );

				if ( is_wp_error( $alias ) ) {
					continue;
				}

				if ( $alias->set_status( 'active' ) ) {
					$processed[] = $alias_id;
				}
			}
			break;

		// Bulk deactivate
		case 'deactivate':
			foreach ( $aliases as $alias_id ) {
				$alias = WP_Site_Alias::get( $alias_id );

				if ( is_wp_error( $alias ) ) {
					continue;
				}

				if ( $alias->set_status( 'inactive' ) ) {
					$processed[] = $alias_id;
				}
			}
			break;

		// Single Add
		case 'add' :
			check_admin_referer( "site_alias_add-{$site_id}" );

			// Check that the parameters are correct first
			$params = wp_site_aliases_validate_alias_parameters( wp_unslash( $_POST ) );

			if ( is_wp_error( $params ) ) {
				$messages[] = $params->get_error_message();

				if ( $params->get_error_code() === 'wp_site_aliases_domain_invalid_chars' ) {
					$messages[] = __( 'Internationalized domain names must use the ASCII version (e.g, <code>xn--bcher-kva.example</code>)', 'wp-site-aliases' );
				}

				return $messages;
			}

			// Add
			$alias = WP_Site_Alias::create( $params['site'], $params['domain'], $params['status'] );

			// Bail if an error occurred
			if ( is_wp_error( $alias ) ) {
				$messages[] = $alias->get_error_message();
				return $messages;
			}

			$processed[] = $alias_id;
			break;

		// Single Edit
		case 'edit' :
			check_admin_referer( "site_alias_edit-{$site_id}" );

			// Check that the parameters are correct first
			$params = wp_site_aliases_validate_alias_parameters( wp_unslash( $_POST ) );

			if ( is_wp_error( $params ) ) {
				$messages[] = $params->get_error_message();

				if ( $params->get_error_code() === 'wp_site_aliases_domain_invalid_chars' ) {
					$messages[] = __( 'Internationalized domain names must use the ASCII version (e.g, <code>xn--bcher-kva.example</code>)', 'wp-site-aliases' );
				}

				return $messages;
			}

			$alias_id = $aliases[0];
			$alias = WP_Site_Alias::get( $alias_id );

			if ( is_wp_error( $alias ) ) {
				$messages[] = $alias->get_error_message();
				return $messages;
			}

			// Update
			$result = $alias->update( $params );

			// Bail if an error occurred
			if ( is_wp_error( $result ) ) {
				$messages[] = $result->get_error_message();
				return $messages;
			}

			$processed[] = $alias_id;

			break;

		// Single/Bulk Delete
		case 'delete':
			$args['domains'] = array();

			foreach ( $aliases as $alias_id ) {
				$alias = WP_Site_Alias::get( $alias_id );

				if ( is_wp_error( $alias ) ) {
					continue;
				}

				// Aliases don't exist after we delete them, so pass the
				// domain for messages and such
				if ( $alias->delete() ) {
					$args['domains'][] = $alias->get_domain();
					$processed++;
				}
			}
			break;

		// Any other bingos
		default:
			check_admin_referer( "site_aliases-bulk-{$site_id}" );
			do_action_ref_array( "aliases_bulk_action-{$action}", array( $aliases, &$processed, $action ) );

			break;
	}

	$args['processed'] = $processed;
	$redirect_to = add_query_arg( $args, $redirect_to );

	// Success, redirect to alias page
	$redirect_to = wp_site_aliases_admin_url( array(
		'page'       => 'site_aliases',
		'id'         => $site_id,
		'did_action' => 'add',
		'aliases'    => $alias_id,
		'processed'  => 1,
		'_wpnonce'   => wp_create_nonce( "site_alias-add-{$alias_id}" ),
	) );

	wp_safe_redirect( $redirect_to );
	exit();
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
	$form_url    = wp_site_aliases_admin_url( array( 'page'   => 'site_aliases'   ) );
	$action_url  = wp_site_aliases_admin_url( array( 'action' => 'site_alias_add' ) );

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
						</div>
						<div class="form-field form-required status-wrap">
							<label for="status"><?php echo esc_html_x( 'Status', 'field name', 'wp-site-aliases' ); ?></label>
							<label>
								<input type="checkbox" name="status" <?php checked( true ); ?> />

								<?php esc_html_e( 'Active', 'wp-site-aliases' ); ?>
							</label>
							<p><?php esc_html_e( 'Whether this domain is active and ready to accept requests.', 'wp-site-aliases' ); ?></p>
						</div>

						<input type="hidden" name="id" value="<?php echo esc_attr( $site_id ); ?>">
						<?php

						wp_nonce_field( "site_alias_add-{$site_id}" );

						submit_button( esc_html__( 'Add New Alias', 'wp-site-aliases' ) );

						?>
					</form>
				</div>
			</div>
		</div>
	</div><?php
}

/**
 * Validate alias parameters
 *
 * @since 0.1.0
 *
 * @param  array    $params            Raw input parameters
 * @param  boolean  $check_permission  Should we check that the user can edit
 *                                     the network?
 *
 * @return array|WP_Error Validated parameters on success, WP_Error otherwise
 */
function wp_site_aliases_validate_alias_parameters( $params, $check_permission = true ) {
	$valid = array();

	// Strip schemes from domain
	$params['domain'] = preg_replace( '#^https?://#', '', rtrim( $params['domain'], '/' ) );

	// Bail if no domain name
	if ( empty( $params['domain'] ) ) {
		return new WP_Error( 'wp_site_aliases_no_domain', __( 'Aliases require a domain name', 'wp-site-aliases' ) );
	}

	// Bail if domain name using invalid characters
	if ( ! preg_match( '#^[a-z0-9\-.]+$#i', $params['domain'] ) ) {
		return new WP_Error( 'wp_site_aliases_domain_invalid_chars', __( 'Domains can only contain alphanumeric characters, dashes (-) and periods (.)', 'wp-site-aliases' ) );
	}

	$valid['domain'] = $params['domain'];

	// Bail if site ID is not valid
	$valid['site'] = absint( $params['id'] );
	if ( empty( $valid['site'] ) ) {
		return new WP_Error( 'wp_site_aliases_invalid_site', __( 'Invalid site ID', 'wp-site-aliases' ) );
	}

	if ( true === $check_permission ) {
		$details = get_blog_details( $valid['site'] );

		// Bail if user cannot edit the network
		if ( ! can_edit_network( $details->site_id ) ) {
			return new WP_Error( 'wp_site_aliases_cannot_edit', __( 'You do not have permission to edit this site', 'wp-site-aliases' ) );
		}
	}

	// Validate status
	$valid['status'] = empty( $params['status'] )
		? 'inactive'
		: 'active';

	return $valid;
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_edit_page() {

	// Defaults of Add
	$alias_id   = 0;
	$alias      = null;
	$site_id    = wp_site_aliases_get_site_id();
	$action     = 'add';
	$action_url = wp_site_aliases_admin_url( array( 'page' => 'site_aliases', 'action' => 'site_alias_add' ) );

	// Edit
	if ( ! empty( $_REQUEST['aliases'] ) ) {
		$alias_id   = absint( $_REQUEST['aliases'] );
		$alias      = WP_Site_Alias::get( $alias_id );
		$action     = 'edit';
		$action_url = wp_site_aliases_admin_url( array( 'page' => 'site_aliases', 'action' => 'site_alias_edit' ) );
	}

	// Output the header, maybe with network site tabs
	wp_site_aliases_output_page_header( $site_id );

	// Add
	if ( empty( $alias ) || ! empty( $_POST['_wpnonce'] ) ) {
		$domain = empty( $_POST['domain'] ) ? '' : wp_unslash( $_POST['domain'] );
		$active = ! empty( $_POST['active'] );

	// Edit
	} else {
		$domain = $alias->get_domain();
		$active = ( 'active' === $alias->get_status() );
	} ?>

	<form method="post" action="<?php echo esc_url( $action_url ); ?>">
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
			</tr>
		</table>

		<input type="hidden" name="action"  value="<?php echo esc_attr( $action   ); ?>">
		<input type="hidden" name="id"      value="<?php echo esc_attr( $site_id  ); ?>">
		<input type="hidden" name="aliases" value="<?php echo esc_attr( $alias_id ); ?>"><?php

		// Add
		if ( 'add' === $action ) {
			wp_nonce_field( "site_alias_add-{$site_id}" );
			submit_button( esc_html__( 'Add Alias', 'wp-site-aliases' ) );

		// Edit
		} else {
			wp_nonce_field( "site_alias_edit-{$site_id}" );
			submit_button( esc_html__( 'Save Alias', 'wp-site-aliases' ) );
		}

	?></form>

<?php
}

function wp_site_aliases_admin_notices() {
	global $wp_list_table;

	$site_id     = wp_site_aliases_get_site_id();
	$bulk_action = $wp_list_table->current_action();
	$messages    = ! empty( $bulk_action )
		? wp_site_aliases_handle_list_page_submit( $site_id, $bulk_action )
		: array();

	// Add messages for bulk actions
	if ( ! empty( $_REQUEST['did_action'] ) ) {
		$processed  = empty( $_REQUEST['processed'] ) ? 0 : absint( $_REQUEST['processed'] );
		$did_action = $_REQUEST['did_action'];

		$aliases = empty( $_REQUEST['aliases'] ) ? array() : wp_parse_id_list( $_REQUEST['aliases'] );
		$aliases = array_map( 'absint', $aliases );

		// Special case for single, as it's not really a "bulk" action
		if ( $processed === 1 ) {
			$bulk_messages = array(
				'activate'   => __( 'Activated %s',   'wp-site-aliases' ),
				'deactivate' => __( 'Deactivated %s', 'wp-site-aliases' ),
				'delete'     => __( 'Deleted %s',     'wp-site-aliases' ),
				'add'        => __( 'Added %s',       'wp-site-aliases' ),
				'edit'       => __( 'Updated %s',     'wp-site-aliases' ),
			);
			if ( $did_action !== 'delete' ) {
				$alias  = WP_Site_Alias::get( $aliases[0] );
				$domain = $alias->get_domain();
			} else {
				$domain = empty( $_REQUEST['domains'] ) ? array() : $_REQUEST['domains'][0];
			}
			$placeholder = '<code>' . $domain . '</code>';

		// Note: we still use _n for languages which have special cases on
		// e.g. 3, 5, 10, etc
		} else {
			$bulk_messages = array(
				'activate'   => _n( '%s alias activated.',   '%s aliases activated.',   $processed ),
				'deactivate' => _n( '%s alias deactivated.', '%s aliases deactivated.', $processed ),
				'delete'     => _n( '%s alias deleted.',     '%s aliases deleted.',     $processed ),
				'add'        => _n( '%s alias added.',       '%s aliases added.',       $processed ),
				'edit'       => _n( '%s alias updated.',     '%s aliases updated.',     $processed ),
			);
			$placeholder = number_format_i18n( $processed );
		}

		$bulk_messages = apply_filters( 'aliases_bulk_messages', $bulk_messages, $processed );

		if ( ! empty( $bulk_messages[ $did_action ] ) ) {
			$messages[] = sprintf( $bulk_messages[ $did_action ], $placeholder );
		}
	}

	echo implode( '', $messages );
}
