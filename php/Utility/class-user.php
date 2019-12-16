<?php
/**
 * Get user capabilities.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Utility;

use \MultisiteSearch\Utility\Logger;

/**
 * User.
 */
class User {

	const CORE_CAPS = array(
		'switch_themes',
		'edit_themes',
		'edit_theme_options',
		'install_themes',
		'activate_plugins',
		'edit_plugins',
		'install_plugins',
		'edit_users',
		'edit_files',
		'manage_options',
		'moderate_comments',
		'manage_categories',
		'manage_links',
		'upload_files',
		'import',
		'unfiltered_html',
		'edit_posts',
		'edit_others_posts',
		'edit_published_posts',
		'publish_posts',
		'edit_pages',
		'read',
		'publish_pages',
		'edit_others_pages',
		'edit_published_pages',
		'delete_pages',
		'delete_others_pages',
		'delete_published_pages',
		'delete_posts',
		'delete_others_posts',
		'delete_published_posts',
		'delete_private_posts',
		'edit_private_posts',
		'read_private_posts',
		'delete_private_pages',
		'edit_private_pages',
		'read_private_pages',
		'delete_users',
		'create_users',
		'unfiltered_upload',
		'edit_dashboard',
		'customize',
		'delete_site',
		'update_plugins',
		'delete_plugins',
		'update_themes',
		'update_core',
		'list_users',
		'remove_users',
		'add_users',
		'promote_users',
		'delete_themes',
		'export',
		'edit_comment',
		'create_sites',
		'delete_sites',
		'manage_network',
		'manage_sites',
		'manage_network_users',
		'manage_network_themes',
		'manage_network_options',
		'manage_network_plugins',
		'upload_plugins',
		'upload_themes',
		'upgrade_network',
		'setup_network',
		'level_10',
		'level_9',
		'level_8',
		'level_7',
		'level_6',
		'level_5',
		'level_4',
		'level_3',
		'level_2',
		'level_1',
		'level_0',
		'administrator',
	);

	/**
	 * Get current capabilities of user.
	 *
	 * @param int     $user_id The user to check (or 0 for current user).
	 * @param boolean $as_string Return a concatenated list?  Or an array.
	 * @param boolean $ignore_core_capabilities Ignore core WP capabilities for better comparisons.
	 * @param string  $glue The character to separate items in a list.
	 *
	 * @return mixed
	 */
	public static function get_capabilities( $user_id = 0, $as_string = true, $ignore_core_capabilities = true, $glue = ',' ) {

		global $wpdb;

		if ( ! \is_user_logged_in() ) {
			return $as_string ? '' : array();
		}

		$user_id = empty( $user_id ) ? \get_current_user_id() : (int) $user_id;

		$cache_key = md5( 'multisite_search_user_meta::' . $user_id );

		$caps = wp_cache_get( $cache_key, 'multisite_search' );

		if ( false === $caps ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"
					SELECT *
					FROM $wpdb->usermeta
					WHERE user_id=%s AND meta_key REGEXP 'wp.*_capabilities';
					",
					$user_id
				)
			);
			$caps    = self::process_caps( $results, $ignore_core_capabilities );
			wp_cache_set( $cache_key, $caps, 'multisite_search', rand( 5 * MINUTE_IN_SECONDS, 15 * MINUTE_IN_SECONDS ) );
		}

		return $as_string ? implode( $glue, $caps ) : $caps;
	}

	/**
	 * Process `wp_*_capabilities` results into capabilities arrays.
	 *
	 * @param array $mysql_result_array The result set.
	 * @param bool  $ignore_core_capabilities Maybe we ignore core capabilities.
	 * @return mixed
	 */
	private static function process_caps( $mysql_result_array, $ignore_core_capabilities ) {
		if ( ! is_array( $mysql_result_array ) ) {
			return array();
		}

		$caps = array();
		foreach ( $mysql_result_array as $result ) {
			$blog_id  = (int) str_replace( array( 'wp', '_', 'capabilities' ), '', $result->meta_key );
			$blog_id  = empty( $blog_id ) ? 1 : $blog_id;
			$raw_caps = \maybe_unserialize( $result->meta_value );

			if ( is_array( $raw_caps ) ) {
				foreach ( $raw_caps as $cap => $enabled ) {
					if ( ! empty( $enabled ) ) {
						if ( $ignore_core_capabilities && in_array( $cap, self::CORE_CAPS, true ) ) {
							continue;
						}
						array_push( $caps, $blog_id . ':' . $cap );
					}
				}
			}
		}

		return $caps;
	}

}
