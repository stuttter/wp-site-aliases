<?php

/**
 * Site Aliases Admin
 *
 * @package Plugins/Site/Aliases/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

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

	// Bail if not in network admin
	if ( ! is_network_admin() ) {
		return;
	}

	// Bail if not looking at sites.php
	if ( $GLOBALS['parent_file'] !== 'sites.php' || $GLOBALS['submenu_file'] !== 'sites.php' ) {
		return;
	}

	// Bail if no ID
	$id = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
	if ( empty( $id ) ) {
		return;
	}

	// Look for active tab
	$class  = ! empty( $_REQUEST['action'] ) && in_array( sanitize_key( $_REQUEST['action'] ), array( 'site_aliases', 'site_alias_edit', 'site_alias_add' ), true )
		? ' nav-tab-active'
		: ''; ?>

	<span id="wp-site-aliases-nav-link" class="hide-if-no-js">
		<a href="<?php echo network_admin_url( add_query_arg( array( 'action' => 'site_aliases', 'id' => $id ), 'admin.php' ) ); ?>" class="nav-tab<?php echo esc_attr( $class ); ?>"><?php esc_html_e( 'Aliases', 'wp-site-aliases' ) ?></a>
	</span>
	<script>jQuery( function wp_site_aliases( $ ) { $( '#wp-site-aliases-nav-link' ).appendTo( $( '.nav-tab-wrapper' ) ); } );</script>

<?php
}

/**
 * Output the admin page header
 *
 * @since 0.1.0
 *
 * @param  int    $id        Site ID
 * @param  array  $messages  Messages to display
 */
function wp_site_aliases_output_page_header( $id, $messages = array() ) {
	global $title, $parent_file, $submenu_file, $pagenow;

	// Header
	$details      = get_blog_details( $id );
	$title        = sprintf( __( 'Edit Site: %s' ), esc_html( $details->blogname ) );
	$parent_file  = 'sites.php';
	$submenu_file = 'sites.php';

	// Pull in admin header
	require_once ABSPATH . 'wp-admin/admin-header.php'; ?>

	<div class="wrap">
		<h1 id="edit-site"><?php echo $title; ?></h1>
		<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>

		<h3 class="nav-tab-wrapper"><?php

		$tabs = array(
			'site-info'     => array( 'label' => __( 'Info'     ), 'url' => 'site-info.php'     ),
			'site-users'    => array( 'label' => __( 'Users'    ), 'url' => 'site-users.php'    ),
			'site-themes'   => array( 'label' => __( 'Themes'   ), 'url' => 'site-themes.php'   ),
			'site-settings' => array( 'label' => __( 'Settings' ), 'url' => 'site-settings.php' ),
		);

		foreach ( $tabs as $tab ) {
			$class = ( $tab['url'] === $pagenow ) ? ' nav-tab-active' : '';
			echo '<a href="' . esc_url( add_query_arg( array( 'id' => $id ), $tab['url'] ) ) . '" class="nav-tab' . $class . '">' . esc_html( $tab['label'] ) . '</a>';
		}

		?></h3><?php

	// Output feedback
	if ( ! empty( $messages ) ) {
		foreach ( $messages as $msg ) {
			echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
		}
	}
}

/**
 * Output the admin page footer
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_page_footer() {
	echo '</div>';

	require_once ABSPATH . 'wp-admin/admin-footer.php';
}

/**
 * Handle submission of the list page
 *
 * Handles bulk actions for the list page. Redirects back to itself after
 * processing, and exits.
 *
 * @since 0.1.0
 *
 * @param  int     $id      Site ID
 * @param  string  $action  Action to perform
 */
function wp_site_aliases_handle_list_page_submit( $id, $action ) {
	global $parent_file;

	check_admin_referer( 'site_aliases-bulk-' . $id );

	// Setup the URL to redirect to on save
	$redirect_to = remove_query_arg( array( 'did_action', 'aliases', '_wpnonce' ), wp_get_referer() );
	if ( empty( $redirect_to ) ) {
		$redirect_to = admin_url( $parent_file );
	}

	// Get aliases being bulk actioned
	$aliases = empty( $_REQUEST['aliases'] )
		? array()
		: array_map( 'absint', (array) $_REQUEST['aliases'] );

	// Bail if no aliases
	if ( empty( $aliases ) ) {
		wp_redirect( $redirect_to );
		exit;
	}

	$processed = 0;
	$args = array(
		'did_action' => $action,
		'aliases'    => join( ',', $aliases ),
	);

	switch ( $action ) {
		case 'activate':
			foreach ( $aliases as $id ) {
				$alias = WP_Site_Alias::get( $id );

				if ( is_wp_error( $alias ) ) {
					continue;
				}

				if ( $alias->set_active( true ) ) {
					$processed++;
				}
			}
			break;

		case 'deactivate':
			foreach ( $aliases as $id ) {
				$alias = WP_Site_Alias::get( $id );

				if ( is_wp_error( $alias ) ) {
					continue;
				}

				if ( $alias->set_active( false ) ) {
					$processed++;
				}
			}
			break;

		case 'delete':
			$args['domains'] = array();
			foreach ( $aliases as $id ) {
				$alias = WP_Site_Alias::get( $id );

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

		default:
			do_action_ref_array( "aliases_bulk_action-{$action}", array( $aliases, &$processed, $action ) );
			break;
	}

	$args['processed'] = $processed;
	$redirect_to = add_query_arg( $args, $redirect_to );

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
	$id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: 0;

	// No site ID
	if ( empty( $id ) ) {
		wp_die( __( 'Invalid site ID.' ) );
	}

	// Get blog details
	$id      = absint( $id );
	$details = get_blog_details( $id );

	// Bail if user cannot access this network
	if ( ! can_edit_network( $details->site_id ) || (int) $details->blog_id !== $id ) {
		wp_die( __( 'You do not have permission to access this page.', 'wp-site-aliases' ) );
	}

	// Include the list table class
	require_once dirname( __FILE__ ) . '/class-wp-site-aliases-list-table.php';

	// Create a new list table object
	$wp_list_table = new WP_Site_Aliases_List_Table( array(
		'site_id' => $id,
	) );

	$pagenum     = $wp_list_table->get_pagenum();
	$bulk_action = $wp_list_table->current_action();
	$messages    = ! empty( $bulk_action )
		? wp_site_aliases_handle_list_page_submit( $id, $bulk_action )
		: array();

	$wp_list_table->prepare_items( $id );

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

	wp_site_aliases_output_page_header( $id, $messages ); ?>

	<div id="col-container" style="margin-top: 20px;">
		<div id="col-right">
			<div class="col-wrap">
				<div class="form-wrap">
					<form method="post" action="admin.php?action=site_aliases">
						<?php $wp_list_table->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h2><?php esc_html_e( 'Add New Alias', 'wp-site-aliases' ); ?></h2>
					<form method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'site_alias_add' ), network_admin_url( 'admin.php' ) ) ); ?>">
						<div class="form-field form-required domain-wrap">
							<label for="blog_alias"><?php echo esc_html_x( 'Domain Name', 'field name', 'wp-site-aliases' ) ?></label>
							<input type="text" class="regular-text code" name="domain" id="blog_alias" value="" />
							<p><?php esc_html_e( 'The fully qualified domain name that this site should load for.', 'wp-site-aliases' ); ?></p>
						</div>
						<div class="form-field form-required active-wrap">
							<label for="active"><?php echo esc_html_x( 'Status', 'field name', 'wp-site-aliases' ) ?></label>
							<label>
								<input type="checkbox" name="active" <?php checked( true ); ?> />

								<?php esc_html_e( 'Active', 'wp-site-aliases' ); ?>
							</label>
							<p><?php esc_html_e( 'Whether this domain is active and ready to accept requests.', 'wp-site-aliases' ); ?></p>
						</div>

						<input type="hidden" name="id" value="<?php echo esc_attr( $id ) ?>" />
						<?php

						wp_nonce_field( 'site_alias_add-' . $id );

						submit_button( esc_html__( 'Add New Alias', 'wp-site-aliases' ) );

						?>
					</form>
				</div>
			</div>
		</div>
	</div>

<?php

	wp_site_aliases_output_page_footer();

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

	// Validate active flag
	$valid['active'] = empty( $params['active'] ) ? false : true;

	return $valid;
}

/**
 * Handle submission of the add page
 *
 * @since 0.1.0
 *
 * @return  array|null  List of errors. Issues a redirect and exits on success.
 */
function wp_site_aliases_handle_edit_page_submit( $id, $alias ) {
	$messages = array();

	if ( empty( $alias ) ) {
		$did_action = 'add';
		check_admin_referer( 'site_alias_add-' . $id );
	} else {
		$did_action = 'edit';
		check_admin_referer( 'site_alias_edit-' . $alias->get_id() );
	}

	// Check that the parameters are correct first
	$params = wp_site_aliases_validate_alias_parameters( wp_unslash( $_POST ) );
	if ( is_wp_error( $params ) ) {
		$messages[] = $params->get_error_message();

		if ( $params->get_error_code() === 'wp_site_aliases_domain_invalid_chars' ) {
			$messages[] = __( 'Internationalized domain names must use the ASCII version (e.g, <code>xn--bcher-kva.example</code>)', 'wp-site-aliases' );
		}

		return $messages;
	}

	// Create the actual alias
	if ( empty( $alias ) ) {
		$result = WP_Site_Alias::create( $params['site'], $params['domain'], $params['active'] );

		if ( ! is_wp_error( $result ) ) {
			$alias = $result;
		}

	// Update our existing
	} else {
		$result = $alias->update( $params );
	}

	if ( is_wp_error( $result ) ) {
		$messages[] = $result->get_error_message();

		return $messages;
	}

	// Success, redirect to alias page
	$location = add_query_arg( array(
		'action'     => 'site_aliases',
		'id'         => $id,
		'did_action' => $did_action,
		'aliases'    => $alias->get_id(),
		'processed'  => 1,
		'_wpnonce'   => wp_create_nonce( 'site_alias-add-' . $alias->get_id() ),
	), network_admin_url( 'admin.php' ) );

	wp_safe_redirect( $location );

	exit;
}

/**
 * Output alias editing page
 *
 * @since 0.1.0
 */
function wp_site_aliases_output_edit_page() {

	$id = isset( $_REQUEST['id'] )
		? intval( $_REQUEST['id'] )
		: 0;

	if ( empty( $id ) ) {
		wp_die( __('Invalid site ID.') );
	}

	$id      = absint( $id );
	$details = get_blog_details( $id );

	if ( ! can_edit_network( $details->site_id ) || (int) $details->blog_id !== $id ) {
		wp_die( __( 'You do not have permission to access this page.' ) );
	}

	// Are we editing?
	$alias       = null;
	$form_action = network_admin_url( 'admin.php?action=site_alias_add' );

	if ( ! empty( $_REQUEST['alias'] ) ) {

		$alias_id = absint( $_REQUEST['alias'] );
		$alias    = WP_Site_Alias::get( $alias_id );

		if ( is_wp_error( $alias ) || empty( $alias ) ) {
			wp_die( __( 'Invalid alias ID.', 'wp-site-aliases' ) );
		}

		$form_action = network_admin_url( 'admin.php?action=site_alias_edit' );
	}

	// Handle form submission
	$messages = array();
	if ( ! empty( $_POST['submit'] ) ) {
		$messages = wp_site_aliases_handle_edit_page_submit( $id, $alias );
	}

	wp_site_aliases_output_page_header( $id, $messages );

	if ( empty( $alias ) || ! empty( $_POST['_wpnonce'] ) ) {
		$domain = empty( $_POST['domain'] ) ? '' : wp_unslash( $_POST['domain'] );
		$active = ! empty( $_POST['active'] );
	} else {
		$domain = $alias->get_domain();
		$active = $alias->is_active();
	} ?>

	<form method="post" action="<?php echo esc_url( $form_action ) ?>">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="blog_alias"><?php echo esc_html_x( 'Domain Name', 'field name', 'wp-site-aliases' ) ?></label>
				</th>
				<td>
					<input type="text" class="regular-text code" name="domain" id="blog_alias" value="<?php echo esc_attr( $domain ) ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php echo esc_html_x( 'Status', 'field name', 'wp-site-aliases' ) ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="active" <?php checked( $active ) ?> />

						<?php esc_html_e( 'Active', 'wp-site-aliases' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<input type="hidden" name="id" value="<?php echo esc_attr( $id ) ?>" />
		<?php

		if ( empty( $alias ) ) {
			wp_nonce_field( 'site_alias_add-' . $id );
			submit_button( __( 'Add Alias', 'wp-site-aliases' ) );
		} else {
			echo '<input type="hidden" name="alias" value="' . esc_attr( $alias->get_id() ) . '" />';
			wp_nonce_field( 'site_alias_edit-' . $alias->get_id() );
			submit_button( __( 'Save Alias', 'wp-site-aliases' ) );
		}

		?>
	</form>

<?php

	wp_site_aliases_output_page_footer();
}
