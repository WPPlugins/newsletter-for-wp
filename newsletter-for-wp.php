<?php
/*
Plugin Name: Newsletter for WordPress
Description: Newsletter for WordPress by Morloi. Adds various highly effective sign-up methods to your site. Port of Mailchimp for Wordpress
Version: 1.0.1
Author: Morloi
Author URI: https://morloi.org/
Text Domain: newsletter-for-wp
Domain Path: /languages
License: GPL v3

Mailchimp for WordPress
Copyright (C) 2012-2016, Danny van Kooten, hi@dannyvankooten.com

Newsletter for WordPress
Copyright (C) 2016, Alessandro Morloi Grazioli, morloi@morloi.org

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Prevent direct file access
defined( 'ABSPATH' ) or exit;

/**
 * Bootstrap the NewsLetter for WordPress plugin
 *
 * @ignore
 * @access private
 * @return bool
 */
function __nl4wp_load_plugin() {

	global $nl4wp;

	// Don't run if NewsLetter for WP Pro 2.x is activated
	if( defined( 'NL4WP_VERSION' ) ) {
		return false;
	}

	// bootstrap the core plugin
	define( 'NL4WP_VERSION', '1.0.1' );// VERSIONE NL
	define('NL4WP_PREMIUM_VERSION', true); // PREMIUM??
	define( 'NL4WP_PLUGIN_DIR', dirname( __FILE__ ) . '/' );
	define( 'NL4WP_PLUGIN_URL', plugins_url( '/' , __FILE__ ) );
	define( 'NL4WP_PLUGIN_FILE', __FILE__ );

	// load autoloader
	require_once NL4WP_PLUGIN_DIR . 'vendor/autoload_52.php';

	/**
	 * @global NL4WP_Container $GLOBALS['nl4wp']
	 * @name $nl4wp
	 */
	$nl4wp = nl4wp();
	$nl4wp['api'] = 'nl4wp_get_api';
	$nl4wp['request'] = array( 'NL4WP_Request', 'create_from_globals' );
	$nl4wp['log'] = 'nl4wp_get_debug_log';

	// forms
	$nl4wp['forms'] = new NL4WP_Form_Manager();
	$nl4wp['forms']->add_hooks();

	// integration core
	$nl4wp['integrations'] = new NL4WP_Integration_Manager();
	$nl4wp['integrations']->add_hooks();

	// bootstrap custom integrations
	require_once NL4WP_PLUGIN_DIR . 'integrations/bootstrap.php';

	// Doing cron? Load Usage Tracking class.
	if( defined( 'DOING_CRON' ) && DOING_CRON ) {
		NL4WP_Usage_Tracking::instance()->add_hooks();
	}

	// Initialize admin section of plugin
	if( is_admin()
	    && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

		$messages = new NL4WP_Admin_Messages();
		$nl4wp['admin.messages'] = $messages;

		$newsletter = new NL4WP_NewsLetter();

		$admin = new NL4WP_Admin( $messages, $newsletter );
		$admin->add_hooks();

		$forms_admin = new NL4WP_Forms_Admin( $messages, $newsletter );
		$forms_admin->add_hooks();

		$integrations_admin = new NL4WP_Integration_Admin( $nl4wp['integrations'], $messages, $newsletter );
		$integrations_admin->add_hooks();
	}

	return true;
}

add_action( 'plugins_loaded', '__nl4wp_load_plugin', 20 );

/**
 * Flushes all NewsLetter caches
 *
 * @ignore
 * @access private
 * @since 3.0
 */
function __nl4wp_flush() {
	delete_transient( 'nl4wp_newsletter_lists' );
	delete_transient( 'nl4wp_newsletter_lists_fallback' );
}

register_activation_hook( __FILE__, '__nl4wp_flush' );
