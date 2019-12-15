<?php
/**
 * WP CLI Table management.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\CLI;

use MultisiteSearch\ComponentAbstract;

/**
 * Class Table
 */
class Table {

	/**
	 * Create the Multisite Search index table.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args The args.
	 * @return void
	 */
	public function create( $args ) {
		$db = new \MultisiteSearch\Admin\Database();
		$db->create_index_table();
		\WP_CLI::success( 'Created Multisite Search Index.' );
	}

	/**
	 * Drop the Multisite Search index table.
	 *
	 * *WARNING*: The indexed entries will be removed permanently.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args The args.
	 * @return void
	 */
	public function drop( $args ) {
		$db = new \MultisiteSearch\Admin\Database();
		$db->drop_index_table();
		\WP_CLI::success( 'Dropped Multisite Search Index.' );
	}
}
