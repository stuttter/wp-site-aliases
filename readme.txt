=== WP Site Aliases ===
Contributors: johnjamesjacoby, stuttter
Tags: blog, site, meta, multisite, alias, domain, mapping
Requires at least: 4.5
Tested up to: 4.8
Stable tag: 4.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9Q4F4EL5YJ62J

== Description ==

The best way to allow custom domains in your WordPress Multisite installation

= Details =

* Seamlessly integrates into WordPress's dashboard interface
* Comes with turn-key `sunrise.php` drop-in
* Easily enable & disable aliases
* Safe, secure, & designed to scale

= Works great with =

* [WP Chosen](https://wordpress.org/plugins/wp-chosen/ "Make long, unwieldy select boxes much more user-friendly.")
* [WP Blog Meta](https://wordpress.org/plugins/wp-blog-meta/ "A global, joinable meta-data table for your WordPress Multisite sites.")
* [WP Event Calendar](https://wordpress.org/plugins/wp-event-calendar/ "The best way to manage events in WordPress.")
* [WP Pretty Filters](https://wordpress.org/plugins/wp-pretty-filters/ "Makes post filters better match what's already in Media & Attachments.")
* [WP Media Categories](https://wordpress.org/plugins/wp-media-categories/ "Add categories to media & attachments.")
* [WP Multi Network](https://wordpress.org/plugins/wp-multi-network/ "A network management interface for global multisite administrators.")
* [WP Term Order](https://wordpress.org/plugins/wp-term-order/ "Sort taxonomy terms, your way.")
* [WP Term Authors](https://wordpress.org/plugins/wp-term-authors/ "Authors for categories, tags, and other taxonomy terms.")
* [WP Term Colors](https://wordpress.org/plugins/wp-term-colors/ "Pretty colors for categories, tags, and other taxonomy terms.")
* [WP Term Families](https://wordpress.org/plugins/wp-term-families/ "Families of taxonomies for taxonomy terms.")
* [WP Term Icons](https://wordpress.org/plugins/wp-term-icons/ "Pretty icons for categories, tags, and other taxonomy terms.")
* [WP Term Images](https://wordpress.org/plugins/wp-term-images/ "Pretty images for categories, tags, and other taxonomy terms.")
* [WP Term Visibility](https://wordpress.org/plugins/wp-term-visibility/ "Visibilities for categories, tags, and other taxonomy terms.")
* [WP User Activity](https://wordpress.org/plugins/wp-user-activity/ "The best way to log activity in WordPress.")
* [WP User Alerts](https://wordpress.org/plugins/wp-user-alerts/ "Alert registered users when new content is published.")
* [WP User Avatars](https://wordpress.org/plugins/wp-user-avatars/ "Allow users to upload avatars or choose them from your media library.")
* [WP User Groups](https://wordpress.org/plugins/wp-user-groups/ "Group users together with taxonomies & terms.")
* [WP User Parents](https://wordpress.org/plugins/wp-user-parents/ "Allow parent users to manage their direct decendants.")
* [WP User Profiles](https://wordpress.org/plugins/wp-user-profiles/ "A sophisticated way to edit users in WordPress.")

== Installation ==

* Ensure `DO_NOT_UPGRADE_GLOBAL_TABLES` is not truthy in your `wp-config.php` or equivalent
* Ensure `wp_should_upgrade_global_tables` is not filtered to be falsey
* Download and install using the built-in WordPress plugin installer
* Network Activate in the "Plugins" area of the network-admin of the main site of your installation (phew!)
* Optionally drop the entire `wp-site-aliases` directory into `mu-plugins`
* No further setup or configuration is necessary

== Frequently Asked Questions ==

= Does this create new database tables? =

Yes. It adds `wp_blog_aliases` & `wp_blog_aliasmeta` to `$wpdb->ms_global_tables`.

= Does this support object caching?

Yes. It uses a global `blog-aliases` cache-group for all meta data.

= Does this modify existing database tables? =
No. All of WordPress's core database tables remain untouched.

= Where can I get support? =

* Basic: https://wordpress.org/support/plugin/wp-site-aliases/
* Priority: https://chat.flox.io/support/channels/wp-site-aliases/

= Where can I find documentation? =

http://github.com/stuttter/wp-site-aliases/

== Changelog ==

= [4.0.0] - 2017-03-17 =
* Single-site support

= [3.0.0] - 2016-12-06 =
* Add support for aliases to redirect vs. mask

= [2.1.0] - 2016-11-15 =
* Improve object cache usage

= [2.0.0] - 2016-09-10 =
* Update meta table key column name

= [1.0.0] - 2016-09-07 =
* Initial release
