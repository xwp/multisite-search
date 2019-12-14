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
class Index {

	/**
	 * Generate search index.
	 *
	 * @param mixed $args The args.
	 * @return void
	 */
	public function generate_index( $args ) {
		\WP_CLI::success( 'Todo' );
	}
}
