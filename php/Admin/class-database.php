<?php
/**
 * Multisite Search DB management.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Admin;

/**
 * Database maintainance class.
 */
class Database {

	const VERSION = '0.1';

	/**
	 * Create new table for Multisite Search.
	 *
	 * @return void
	 */
	public function create_index_table() {
		global $wpdb;

		$chatset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $wpdb->multisite_search (
            blog_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            url text NOT NULL,
            slug text NOT NULL,
            post_title text NOT NULL,
            post_content longtext NOT NULL,
            required_capabilities longtext NOT NULL,
            meta longtext,
            post_type varchar(20) NOT NULL,
            PRIMARY KEY  (blog_id, post_id),
            FULLTEXT  (post_title,post_content),
            FULLTEXT  (required_capabilities),
            KEY  (post_type)
        ) $chatset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbdelta( $sql );
	}

	/**
	 * Drop the entire table.
	 *
	 * WARNING: Can't be undone.
	 *
	 * @return void
	 */
	public function drop_index_table() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS $wpdb->multisite_search" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
