<?php
/**
 * Multisite Search Index management.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Admin;

use \MultisiteSearch\Utility\Logger;

/**
 * Database maintainance class.
 */
class Index {

	/**
	 * Add a given site to the Multisite Search Index.
	 *
	 * @param int   $blog_id The site to index.
	 * @param array $post_type The post types to index.
	 * @param int   $page Unused.
	 * @param int   $posts_per_page Unused.
	 *
	 * @return void
	 */
	public function index_site( $blog_id, $post_type = array( 'post', 'page' ), $page = 0, $posts_per_page = 100 ) {

		global $wpdb;
		// Index the given blog.
		switch_to_blog( $blog_id );
		$args = array(
			'post_status' => array( 'publish' ),
			'post_type'   => $post_type,
		);

		$query = new \WP_Query( $args );

		Logger::log( "Indexing Site: $blog_id" );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->index_post( $blog_id, $query->post );
			}
		}

		restore_current_blog();
	}

	/**
	 * Add a given post to the Multisite Search Index.
	 *
	 * @param int  $blog_id The site to index.
	 * @param int  $post_id The post to index.
	 * @param bool $update Skip insert.
	 *
	 * @return void
	 */
	public function index_post( $blog_id, $post_id, $update = false ) {

		global $wpdb;

		switch_to_blog( $blog_id );

		if ( $post_id instanceof \WP_Post ) {
			$post = $post_id;
		} else {
			$post = get_post( (int) $post_id );
		}

		if ( \is_wp_error( $post ) ) {
			return;
		}

		Logger::log( "Indexing Site: $blog_id, Post: $post->ID" );

		$data = array(
			'blog_id'               => $blog_id,
			'post_id'               => $post->ID,
			'url'                   => $post->uuid,
			'slug'                  => $post->post_name,
			'post_title'            => $post->post_title,
			'post_content'          => str_replace( "\n\n", "\n", wp_strip_all_tags( do_shortcode( $post->post_content ) ) ),
			'required_capabilities' => '',
			'meta'                  => '',
		);

		$where = array(
			'blog_id' => $blog_id,
			'post_id' => $post->ID,
		);

		$where_format = array(
			'%d',
			'%d',
		);

		$format = array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		if ( ! $update && $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->multisite_search,
			$data,
			$format
		) ) {
			Logger::success( '.' );
		} else {

			array_shift( $data );
			array_shift( $data );
			array_shift( $format );
			array_shift( $format );

			if ( $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->multisite_search,
				$data,
				$where,
				$format,
				$where_format
			) ) {
				Logger::success( '.' );
			} else {
				Logger::log( empty( $wpdb->last_error ) ? 'Nothing to update.' : $wpdb->last_error );
			}
		}

		restore_current_blog();
	}
}
