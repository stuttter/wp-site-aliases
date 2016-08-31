# WP Site Aliases

The best way to allow custom domains in your WordPress Multisite installation

* Seamlessly integrates into WordPress's dashboard interface
* Comes with turn-key `sunrise.php` drop-in
* Easily enable & disable aliases
* Safe, secure, & designed to scale

# Installation

* Download and install using the built in WordPress plugin installer
* Network Activate in the "Plugins" area of your network-admin by clicking the "Activate" link
* Optionally drop the entire `wp-site-aliases` directory into `mu-plugins`
* Optionally filter `wp_site_aliases_get_documentation_url` & `wp_site_aliases_get_configuration_url` and write some documentation for your custom application
* No further setup or configuration is necessary

# FAQ

### Does this create new database tables?

Yes. It adds `wp_blog_aliases` and `wp_blog_aliasmeta` to `$wpdb->ms_global_tables`.

(If you use a database drop-in for high-availability, you will need to define these tables yourself.)

### Does this support object caching?

Yes. It uses a global `blog-aliases` cache-group for all alias objects.

### Does this modify existing database tables?

No. All of WordPress's core database tables remain untouched.

### Where can I get support?

The WordPress support forums: https://wordpress.org/support/plugin/wp-site-aliases/

### Can I contribute?

Yes, please!

* Public - https://github.com/stuttter/wp-site-aliases/
* Bleeding - https://code.flox.io/stuttter/wp-site-aliases/
