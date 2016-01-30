<?php

// Multiple Sites
add_filter( 'pre_get_site_by_path',    'wp_site_aliases_check_domain_alias',     10, 2 );
add_filter( 'pre_get_site_by_path',    'wp_site_aliases_check_aliases_for_site', 20, 4 );
add_filter( 'pre_get_network_by_path', 'wp_site_aliases_check_aliases_for_site', 10, 4 );

add_action( 'delete_blog',      'wp_site_aliases_clear_aliases_on_delete'     );
add_action( 'muplugins_loaded', 'wp_site_aliases_register_url_filters',   -10 );

// Multiple Networks

// Admin
add_action( 'manage_sites_custom_column', 'wp_site_aliases_output_site_list_column', 10, 2 );
add_filter( 'wpmu_blogs_columns',         'wp_site_aliases_add_site_list_column'  );
add_action( 'admin_footer',               'wp_site_aliases_maybe_output_site_tab' );

// Admin pages
add_action( 'admin_action_site_aliases',    'wp_site_aliases_output_list_page' );
add_action( 'admin_action_site_alias_add',  'wp_site_aliases_output_edit_page' );
add_action( 'admin_action_site_alias_edit', 'wp_site_aliases_output_edit_page' );
