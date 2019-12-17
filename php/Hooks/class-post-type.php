<?php
/**
 * Hooks into core Post Types to index posts on update.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Hooks;

use MultisiteSearch\ComponentAbstract;

/**
 * Class Post_Type
 */
class Post_Type extends ComponentAbstract {

	/**
	 * Register hooks for this view.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_insert_post', array( $this, 'index_post' ), 10, 3 );
	}

	/**
	 * Index a post when created or re-index post after update.
	 *
	 * @param int                $post_id The post ID.
	 * @param \WP_Post|\WP_Error $post The post object.
	 * @param bool               $update Update the index.
	 * @return void
	 */
	public function index_post( $post_id, $post, $update ) {

		// If this is a revision, don't send the email.
		if ( wp_is_post_revision( $post_id ) || 'published' !== $post->post_status ) {
			return;
		}

		$blog_id = \get_current_blog_id();
		$indexer = new \MultisiteSearch\Admin\Index();
		$indexer->index_post( $blog_id, $post, $update );
	}

}
