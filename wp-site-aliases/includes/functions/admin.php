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
 * @since 1.0.0
 */
function wp_site_aliases_add_menu_item() {

	// Define empty array
	$hooks = array();

	// Network admin page
	if ( is_network_admin() ) {
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_site_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_submenu_page( 'sites.php', esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'edit_site_aliases',   'alias_edit_site', 'wp_site_aliases_output_edit_page' );
		remove_submenu_page( 'sites.php', 'site_aliases'    );
		remove_submenu_page( 'sites.php', 'alias_edit_site' );

		// Network management of all aliases
		$hooks[] = add_menu_page( esc_html__( 'All Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_network_options', 'all_site_aliases', 'wp_site_aliases_output_list_page', 'dashicons-randomize', 6 );
		$hooks[] = add_submenu_page( 'admin.php', esc_html__( 'All Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_network_options', 'alias_edit_site', 'wp_site_aliases_output_edit_page', 'dashicons-randomize', 6 );
		remove_submenu_page( 'admin.php', 'alias_edit_site' );

	// Blog admin page
	} elseif ( is_blog_admin() ) {
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'manage_aliases', 'site_aliases',    'wp_site_aliases_output_list_page' );
		$hooks[] = add_dashboard_page( esc_html__( 'Aliases', 'wp-site-aliases' ), esc_html__( 'Aliases', 'wp-site-aliases' ), 'edit_aliases',   'alias_edit_site', 'wp_site_aliases_output_edit_page' );
		remove_submenu_page( 'index.php', 'alias_edit_site' );
	}

	// Load the list table
	foreach ( $hooks as $hook ) {
		add_action( "load-{$hook}", 'wp_site_aliases_handle_site_actions'       );
		add_action( "load-{$hook}", 'wp_site_aliases_load_site_list_table'      );
		add_action( "load-{$hook}", 'wp_site_aliases_fix_hidden_menu_highlight' );
		add_action( "load-{$hook}", 'wp_site_aliases_admin_add_help_tabs'       );
	}
}

/**
 * Get any admin actions
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_admin_action() {

	$action = false;

	// Regular action
	if ( ! empty( $_REQUEST['action'] ) ) {
		$action = sanitize_key( $_REQUEST['action'] );

	// Bulk action (top)
	} elseif ( ! empty( $_REQUEST['bulk_action'] ) ) {
		$action = sanitize_key( $_REQUEST['bulk_action'] );

	// Bulk action (bottom)
	} elseif ( ! empty( $_REQUEST['bulk_action2'] ) ) {
		$action = sanitize_key( $_REQUEST['bulk_action2'] );
	}

	return $action;
}

/**
 * Load the list table and populate some essentials
 *
 * @since 1.0.0
 */
function wp_site_aliases_load_site_list_table() {
	global $wp_list_table;

	// Include the list table class
	require_once wp_site_aliases_get_plugin_path() . 'includes/classes/class-wp-site-aliases-list-table.php';

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
 * @since 1.0.0
 *
 * @global string $parent_file
 * @global string $submenu_file
 */
function wp_site_aliases_fix_hidden_menu_highlight() {
	global $parent_file, $submenu_file;

	// Network admin
	if ( is_network_admin() ) {
		if ( wp_site_aliases_is_network_edit() ) {
			$parent_file  = 'all_site_aliases';
			$submenu_file = null;
		} elseif ( ! wp_site_aliases_is_network_list() ) {
			$parent_file  = 'sites.php';
			$submenu_file = 'sites.php';
		}

	// Blog admin
	} elseif ( is_blog_admin() ) {
		$parent_file  = 'index.php';
		$submenu_file = 'site_aliases';
	}
}

/**
 * Admin area help tabs
 *
 * @since 1.0.0
 */
function wp_site_aliases_admin_add_help_tabs() {

	// Get current screen
	$screen = get_current_screen();

	// Site admin
	if ( $screen->in_admin( 'site' ) ) {

		// Bail if not an Event type screen
		if ( 'dashboard_page_site_aliases' === $screen->id ) {

			// URLs
			$docs_url   = wp_site_aliases_get_documentation_url();
			$config_url = wp_site_aliases_get_configuration_url();

			// Overview
			$screen->add_help_tab( array(
				'id'      => 'overview',
				'title'   => esc_html__( 'Overview', 'wp-site-aliases' ),
				'content' =>
					'<p>' . esc_html__( 'You can use aliases to define custom domains for your site.',              'wp-site-aliases' ) . '</p>' .
					'<p>' . esc_html__( 'This allows your site to use a domain other than what was assigned here.', 'wp-site-aliases' ) . '</p>'
				) );

			// Adding Aliases
			$screen->add_help_tab( array(
				'id'      => 'adding',
				'title'   => esc_html__( 'Adding Aliases', 'wp-site-aliases' ),
				'content' =>
					'<p>'          . esc_html__( 'You can use aliases to define custom domains for your site.', 'wp-site-aliases' ) . '</p><ul>' .
					'<li><strong>' . esc_html__( 'Domain: ', 'wp-site-aliases' ) . '</strong>' . esc_html__( 'Any domain already under your control, and pointed to this service.', 'wp-site-aliases' ) . '</li>' .
					'<li><strong>' . esc_html__( 'Status: ', 'wp-site-aliases' ) . '</strong>' . esc_html__( 'Whether this domain alias is active or not.',                         'wp-site-aliases' ) . '</li></ul>' ) );

			// Help Sidebar
			$screen->set_help_sidebar(
				'<p><strong>'  . esc_html__( 'For more information:', 'wp-site-aliases' ) . '</strong></p>' .
				'<p><a href="' . esc_url( $docs_url   ) . '" target="_blank">' . esc_html__( 'Documentation', 'wp-site-aliases' ) . '</a>' . '</p>' .
				'<p><a href="' . esc_url( $config_url ) . '" target="_blank">' . esc_html__( 'Configuration', 'wp-site-aliases' ) . '</a>' . '</p>'
			);
		}

	// Network admin
	} elseif ( $screen->in_admin( 'network' ) ) {

		// All Aliases
		if ( 'toplevel_page_all_site_aliases-network' === $screen->id ) {

			// URLs
			$docs_url   = wp_site_aliases_get_documentation_url();
			$config_url = wp_site_aliases_get_configuration_url();

			// Overview
			$screen->add_help_tab( array(
				'id'      => 'overview',
				'title'   => esc_html__( 'Overview', 'wp-site-aliases' ),
				'content' =>
					'<p>' . esc_html__( 'Give sites in this network custom domains to be aliased as.',            'wp-site-aliases' ) . '</p>' .
					'<p>' . esc_html__( 'This allows sites to use domains other than what was assigned to them.', 'wp-site-aliases' ) . '</p>'
				) );

			// Adding Aliases
			$screen->add_help_tab( array(
				'id'      => 'adding',
				'title'   => esc_html__( 'Adding Aliases', 'wp-site-aliases' ),
				'content' =>
					'<p>' . esc_html__( 'Aliases require the following information:', 'wp-site-aliases' ) . '</p><ul>' .
					'<li><strong>' . esc_html__( 'Domain: ', 'wp-site-aliases' ) . '</strong>' . esc_html__( 'Any domain already under your control, and pointed to this service.', 'wp-site-aliases' ) . '</li>' .
					'<li><strong>' . esc_html__( 'Site: ',   'wp-site-aliases' ) . '</strong>' . esc_html__( 'The site (on this network) this alias is for.',                       'wp-site-aliases' ) . '</li>' .
					'<li><strong>' . esc_html__( 'Status: ', 'wp-site-aliases' ) . '</strong>' . esc_html__( 'Whether or not to look for this domain alias.',                       'wp-site-aliases' ) . '</li></ul>' ) );

			// Help Sidebar
			$screen->set_help_sidebar(
				'<p><strong>'  . esc_html__( 'For more information:', 'wp-site-aliases' ) . '</strong></p>' .
				'<p><a href="' . esc_url( $docs_url   ) . '" target="_blank">' . esc_html__( 'Documentation', 'wp-site-aliases' ) . '</a>' . '</p>' .
				'<p><a href="' . esc_url( $config_url ) . '" target="_blank">' . esc_html__( 'Configuration', 'wp-site-aliases' ) . '</a>' . '</p>'
			);
		}
	}
}

/**
 * Return the documentation URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_documentation_url() {
	$url = network_home_url( 'help/sites/aliases/' );
	return apply_filters( 'wp_site_aliases_get_documentation_url', $url );
}

/**
 * Return the configuration URL
 *
 * @since 1.0.0
 *
 * @return string
 */
function wp_site_aliases_get_configuration_url() {
	$url = network_home_url( 'help/sites/aliases/' );
	return apply_filters( 'wp_site_aliases_get_configuration_url', $url );
}

/**
 * Add site list column to list
 *
 * @since 1.0.0
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
 * @since 1.0.0
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
			echo esc_html( $alias->domain ) . '<br>';
		}

	// No aliases
	} else {
		esc_html_e( '&mdash;', 'wp-site-aliases' );
	}
}

/**
 * Add tab to end of tabs array
 *
 * @since 1.0.0
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
 * @since 1.0.0
 *
 * @param  int  $site_id  Site ID
 */
function wp_site_aliases_output_page_header( $site_id = 0 ) {
	global $title;

	// Network
	if ( is_network_admin() && ! wp_site_aliases_is_network_list() ) :

		// Header
		$title = sprintf( esc_html__( 'Edit Site: %s' ), get_blog_option( $site_id, 'blogname' ) );

		// This is copied from WordPress core (sic)
		?><div class="wrap">
			<h1 id="edit-site"><?php echo $title; ?></h1><?php

			// No links in network edit
			if ( ! wp_site_aliases_is_network_edit() ) :

				?><p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $site_id, '/' ) ); ?>"><?php esc_html_e( 'Visit', 'wp-site-aliases' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $site_id ) ); ?>"><?php esc_html_e( 'Dashboard', 'wp-site-aliases' ); ?></a></p><?php

			endif;

			// Admin notices
			do_action( 'wp_site_aliases_admin_notices' );

			// Tabs in network admin
			if ( ! wp_site_aliases_is_network_edit() ) :
				network_edit_site_nav( array(
					'blog_id'  => $site_id,
					'selected' => 'site-aliases'
				) );
			endif;

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
 * @since 1.0.0
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
 * @since 1.0.0
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
	$redirect_to = remove_query_arg( array( 'did_action', 'processed', 'alias_ids', 'referrer', '_wpnonce' ), wp_get_referer() );

	// Maybe fallback redirect
	if ( empty( $redirect_to ) ) {
		$redirect_to = wp_site_aliases_admin_url();
	}

	// Get aliases being bulk actioned
	$processed = array();
	$alias_ids = wp_site_aliases_sanitize_alias_ids();

	// Redirect args
	$args = array(
		'id'         => $site_id,
		'did_action' => $action,
		'page'       => wp_site_aliases_is_network_list()
			? 'all_site_aliases'
			: 'site_aliases',
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
					$args['domains'][] = $alias->domain;
					$processed[]       = $alias_id;
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

			$processed[] = $alias->id;

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
 * @since 1.0.0
 */
function wp_site_aliases_output_edit_page() {

	// Vars
	$site_id  = wp_site_aliases_get_site_id();
	$alias_id = wp_site_aliases_sanitize_alias_ids( true );
	$alias    = WP_Site_Alias::get_instance( $alias_id );
	$action   = ! empty( $alias ) ? 'edit' : 'add';

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
		$active = ( 'active' === $alias->status );
		$domain = $alias->domain;
	}

	// Output the header, maybe with network site tabs
	wp_site_aliases_output_page_header( $site_id );

	?><form method="post" action="<?php echo esc_url( $action_url ); ?>">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="blog_alias"><?php echo esc_html_x( 'Domain', 'field name', 'wp-site-aliases' ); ?></label>
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
					<select name="status" id="status"><?php

						$statuses = wp_site_aliases_get_statuses();

						// Loop throug sites
						foreach ( $statuses as $status ) :

							// Loop through sites
							?><option value="<?php echo esc_attr( $status->id ); ?>" <?php selected( $status->id, $alias->status ); ?>><?php echo esc_html( $status->name ); ?></option><?php

						endforeach;

					?></select>
				</td>
			</tr><?php

			// Site picker for network admin
			if ( is_network_admin() && wp_site_aliases_is_network_edit() ) :

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
								?><option value="<?php echo esc_attr( $site->blog_id ); ?>" <?php selected( $site->blog_id, $site_id ); ?>><?php echo esc_html( $site->domain . $site->path ); ?></option><?php

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
		if ( ! wp_site_aliases_is_network_edit() ) :

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
 * @since 1.0.0
 */
function wp_site_aliases_output_list_page() {
	global $wp_list_table;

	// Get site ID being requested
	$site_id = wp_site_aliases_get_site_id();
	$search  = isset( $_GET['s']    ) ? $_GET['s']                    : '';
	$page    = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'aliases_site';

	// Action URLs
	$form_url = $action_url = wp_site_aliases_admin_url();

	// Output header, maybe with tabs
	wp_site_aliases_output_page_header( $site_id ); ?>

	<div id="col-container" style="margin-top: 20px;">
		<div id="col-right">
			<div class="col-wrap">

				<form class="search-form wp-clearfix" method="get" action="<?php echo esc_url( $form_url ); ?>">
					<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" /><?php

					// Skip site ID for network list
					if ( ! wp_site_aliases_is_network_list() ) :

						?><input type="hidden" name="id" value="<?php echo esc_attr( $site_id ); ?>" /><?php

					endif;

					?><p class="search-box">
						<label class="screen-reader-text" for="alias-search-input"><?php esc_html_e( 'Search Aliases:', 'wp-site-aliases' ); ?></label>
						<input type="search" id="alias-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
						<input type="submit" id="search-submit" class="button" value="<?php esc_html_e( 'Search Aliases', 'wp-site-aliases' ); ?>">
					</p>
				</form>

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
							<label for="blog_alias"><?php echo esc_html_x( 'Domain', 'field name', 'wp-site-aliases' ); ?></label>
							<input type="text" class="regular-text code" name="domain" id="blog_alias" value="">
							<p><?php esc_html_e( 'The fully qualified domain name that this site should load for.', 'wp-site-aliases' ); ?></p>
						</div><?php

							// Site picker for network admin
							if ( is_network_admin() && wp_site_aliases_is_network_list() ) :

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
							<select name="status" id="status"><?php

								$statuses = wp_site_aliases_get_statuses();

								// Loop throug sites
								foreach ( $statuses as $status ) :

									// Loop through sites
									?><option value="<?php echo esc_attr( $status->id ); ?>"><?php echo esc_html( $status->name ); ?></option><?php

								endforeach;

							?></select>
							<p><?php esc_html_e( 'Whether this domain is ready to accept incoming requests.', 'wp-site-aliases' ); ?></p>
						</div>

						<input type="hidden" name="action"  value="add"><?php

						//
						if ( ! wp_site_aliases_is_network_list() ) :

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
 * @since 1.0.0
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
		'wp_site_aliases_alias_domain_exists'   => _x( 'That domain is already registered.', 'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_alias_update_failed'   => _x( 'Update failed.',                     'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_alias_delete_failed'   => _x( 'Delete failed.',                     'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_alias_invalid_id'      => _x( 'Invalid site ID.',                   'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_domain_empty'          => _x( 'Alias missing domain.',              'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_domain_requires_tld'   => _x( 'Alias missing a top-level domain.',  'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_domain_invalid_chars'  => _x( 'Alias contains invalid characters.', 'site aliases', 'wp-site-aliases' ),
		'wp_site_aliases_domain_invalid_status' => _x( 'Status must be active or inactive',  'site aliases', 'wp-site-aliases' )
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
