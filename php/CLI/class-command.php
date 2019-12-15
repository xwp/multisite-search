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
 * Manage your Multisite Search Index.
 *
 * ## EXAMPLES
 *
 *     wp multisite-search
 *
 * @when after_wp_load
 */
class Command {


	/**
	 * Generate search index.
	 *
	 * @param mixed $args The args.
	 * @param mixed $assoc_args Keyd arguments.
	 *
	 * @return void
	 */
	public function find( $args, $assoc_args ) {

		if ( empty( $assoc_args['keywords'] ) ) {
			\WP_CLI::error( '--keywords not provided.' );
		}

		$args = array(
			'page'     => ! empty( $assoc_args['page'] ) ? (int) $assoc_args['page'] : 0,
			'per_page' => ! empty( $assoc_args['per_page'] ) ? (int) $assoc_args['per_page'] : 100,
		);

		$keywords = \sanitize_text_field( $assoc_args['keywords'] );
		$search   = new \MultisiteSearch\Utility\Search();
		$results  = $search->query( $keywords, $args );

		// @TODO: Remove this one you figured out what to do with it.
		\WP_CLI::success( print_r( $results, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
