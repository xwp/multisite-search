<?php
/**
 * Indexer interface.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch;

/**
 * Interface IndexerInterface
 */
interface IndexerInterface {

	/**
	 * Remove a post from the index.
	 * 
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function remove_post_from_index( $post_id );

	/**
	 * Add a given post to the Multisite Search Index.
	 *
	 * @param int  $blog_id The site to index.
	 * @param int  $post_id The post to index.
	 * @param bool $validated Whether post type has been validated.
	 *
	 * @return void
	 */
	public function index_post( $blog_id, $post_id, $validated = false );
}
