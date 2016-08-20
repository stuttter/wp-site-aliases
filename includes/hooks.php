<?php

/**
 * Site Aliases Hooks
 *
 * @package Plugins/Site/Aliases/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assets
add_action( 'admin_enqueue_scripts', 'wp_site_aliases_admin_enqueue_scripts' );

// Capabilities
add_filter( 'map_meta_cap', 'wp_site_aliases_map_meta_cap', 10, 4 );

// Multiple Sites
add_filter( 'pre_get_site_by_path',    'wp_site_aliases_check_domain_alias',     10, 2 );
add_filter( 'pre_get_site_by_path',    'wp_site_aliases_check_aliases_for_site', 20, 4 );
add_filter( 'pre_get_network_by_path', 'wp_site_aliases_check_aliases_for_site', 10, 4 );

add_action( 'delete_blog',      'wp_site_aliases_clear_aliases_on_delete'     );
add_action( 'muplugins_loaded', 'wp_site_aliases_register_url_filters',   -10 );

// Columns
add_action( 'manage_sites_custom_column', 'wp_site_aliases_output_site_list_column', 10, 2 );
add_filter( 'wpmu_blogs_columns',         'wp_site_aliases_add_site_list_column'  );

// Navigation
add_filter( 'network_edit_site_nav_links', 'wp_site_aliases_add_site_tab' );
add_action( 'admin_menu',                  'wp_site_aliases_add_menu_item', 30 );
add_action( 'network_admin_menu',          'wp_site_aliases_add_menu_item', 10 );

// Notices
add_action( 'wp_site_aliases_admin_notices', 'wp_site_aliases_output_admin_notices' );
