<?php
/**
 * Hooks into core Post Types to index posts on update.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Hooks;

use MultisiteSearch\ComponentAbstract;
use MultisiteSearch\IndexerInterface;

/**
 * Class Post_Type
 */
class Post_Type extends ComponentAbstract {
	/**
	 * Instance of the Indexer component.
	 *
	 * @var IndexerInterface
	 */
	private $indexer;
	
	/**
	 * Constructor.
	 * 
	 * @param IndexerInterface $indexer Indexer.
	 */
	public function __construct( IndexerInterface $indexer ) {
		$this->indexer = $indexer;
	}
	
	/**
	 * Register hooks for this view.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_insert_post', array( $this, 'index_post' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'remove_post' ), 10, 3 );
	}

	/**
	 * Remove a post when its status is no longer published.
	 * 
	 * @param string  $new_status new post status.
	 * @param string  $old_status old post status.
	 * @param WP_Post $post_obj WP_Post object.
	 * 
	 * @return boolean
	 */
	public function remove_post( $new_status, $old_status, $post_obj ) {
		if ( $new_status === $old_status || 'publish' === $new_status ) {
			return;
		}

		return $this->indexer->remove_post_from_index( $post_obj->ID );
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
		if ( wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status ) {
			return;
		}

		$blog_id = \get_current_blog_id();
		
		$this->indexer->index_post( $blog_id, $post );
	}
}
