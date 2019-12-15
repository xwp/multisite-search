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
	public function generate( $args ) {

		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				$indexer = new \MultisiteSearch\Admin\Index();
				$indexer->index_site( $site->blog_id );
			}
			return;
		}
	}
}
