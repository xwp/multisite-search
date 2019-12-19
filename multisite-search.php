<?php
/**
 * Multisite Search
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * Plugin Name: Multisite Search
 * Plugin URI: https://xwp.co/
 * Description: Give a description for Multisite Search.
 * Version: 0.1-alpha
 * Author: Rheinard Korf
 * Author URI: https://xwp.co/
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multisite-search
 * Domain Path: languages
 * Network: ${pluginNetworkEnabled}
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Setup the plugin auto loader.
require_once 'php/autoloader.php';

/**
 * Admin notice for incompatible versions of PHP.
 */
function _multisite_search_php_version_error() {
	printf( '<div class="error"><p>%s</p></div>', esc_html( _multisite_search_php_version_text() ) );
}

/**
 * String describing the minimum PHP version.
 *
 * "Namespace" is a PHP 5.3 introduced feature. This is a hard requirement
 * for the plugin structure.
 *
 * "Traits" is a PHP 5.4 introduced feature. Remove "Traits" support from
 * php/autoloader if you want to support a lower PHP version.
 * Remember to update the checked version below if you do.
 *
 * @return string
 */
function _multisite_search_php_version_text() {
	return __( 'Multisite Search plugin error: Your version of PHP is too old to run this plugin. You must be running PHP 5.4 or higher.', 'multisite-search' );
}

// If the PHP version is too low, show warning and return.
if ( version_compare( phpversion(), '5.4', '<' ) ) {
	if ( defined( 'WP_CLI' ) ) {
		WP_CLI::warning( _multisite_search_php_version_text() );
	} else {
		add_action( 'admin_notices', '_multisite_search_php_version_error' );
	}

	return;
}

/**
 * Get the plugin object.
 *
 * @return \MultisiteSearch\PluginInterface
 */
function multisite_search() {
	static $instance;

	if ( null === $instance ) {
		$instance = new \MultisiteSearch\Plugin();
	}

	return $instance;
}

/**
 * Setup the plugin instance.
 */
multisite_search()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __FILE__ )
	->set_slug( 'multisite-search' )
	->set_url( plugin_dir_url( __FILE__ ) );

/**
 * Add our table to the global $wpdb object so that we don't get errors with ->prepare().
 */
global $wpdb;
$wpdb->multisite_search = $wpdb->base_prefix . 'multisite_search';

/**
 * Register plugin components.
 */
multisite_search()
	->register_component( new \MultisiteSearch\API\Search() )
	->register_component( new \MultisiteSearch\Hooks\Post_Type() )
	->register_component( new \MultisiteSearch\Hooks\Search_Results() )
	->register_component( new \MultisiteSearch\View\Search_Box() )
	->register_component( new \MultisiteSearch\View\Network_Admin_Menu() );

/**
 * Add CLI components.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/php/Admin/class-database.php';

	WP_CLI::add_command( 'multisite-search table', '\\MultisiteSearch\\CLI\\Table' );
	WP_CLI::add_command( 'multisite-search index', '\\MultisiteSearch\\CLI\\Index' );
	WP_CLI::add_command( 'multisite-search', '\\MultisiteSearch\\CLI\\Command' );
}

/**
 * Sometimes we need to do some things after the plugin is loaded, so call the PluginInterface::plugin_loaded().
 */
add_action( 'plugins_loaded', array( multisite_search(), 'plugin_loaded' ) );

// Add convenience functions.
require_once 'php/functions.php';
