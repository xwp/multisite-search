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
	const TABLE   = 'multisite_search';

	public function create_index_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE;

		$chatset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            name tinytext NOT NULL,
            text text NOT NULL,
            PRIMARY KEY  (id)
        ) $chatset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$result = dbdelta( $sql );
	}

	public function drop_index_table() {
		global $wpdb;
		$table_name_prepared = $wpdb->prefix . self::TABLE;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name_prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
