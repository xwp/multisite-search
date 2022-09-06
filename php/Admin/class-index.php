<?php
/**
 * Multisite Search Index management.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Admin;

use \MultisiteSearch\IndexerInterface;
use \MultisiteSearch\Utility\Logger;


/**
 * Database maintainance class.
 */
class Index implements IndexerInterface {

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

		// Provide an array of site IDs to skip for indexing.
		$skipped_sites = apply_filters( 'mss_index_skipped_sites', array() );
		
		// Provide an array of post types for indexing.
		$post_types = apply_filters( 'mss_index_include_post_types', $post_type );
		
		if ( \in_array( (int) $blog_id, $skipped_sites, true ) ) {
			return;
		}

		global $wpdb;
		// Index the given blog.
		switch_to_blog( $blog_id );
		$args = array(
			'post_status'    => array( 'publish' ),
			'post_type'      => $post_types,
			'posts_per_page' => -1,
		);

		$query = new \WP_Query( $args );

		Logger::log( "Indexing Site: $blog_id" );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->index_post( $blog_id, $query->post, true );
			}
		}

		restore_current_blog();
	}

	/**
	 * Add a given post to the Multisite Search Index.
	 *
	 * @param int  $blog_id The site to index.
	 * @param int  $post_id The post to index.
	 * @param bool $validated Whether post type has been validated.
	 *
	 * @return void
	 */
	public function index_post( $blog_id, $post_id, $validated = false ) {
		global $wpdb;

		switch_to_blog( $blog_id );

		if ( $post_id instanceof \WP_Post ) {
			$post = $post_id;
		} else {
			$post = get_post( (int) $post_id );
		}

		// This validation only runs when wp_insert_post hook is triggered.
		if ( ! $validated ) { 
			if ( ! $this->do_index_post( $post->post_type ) ) {
				return;
			}
		}
		
		if ( \is_wp_error( $post ) ) {
			return;
		}

		Logger::log( "Indexing Site: $blog_id, Post: $post->ID" );

		$data = array(
			'blog_id'           => $blog_id,
			'post_id'           => $post->ID,
			'url'               => \apply_filters( 'mss_index_url', $post->guid, $post->ID, $blog_id ),
			'slug'              => $post->post_name,
			'post_title'        => $post->post_title,
			'post_content'      => $this->cleanup_content( $post->post_content ),
			'priority_keywords' => \get_post_meta( $post->ID, 'mss_priority_keywords', true ),
			'post_type'         => $post->post_type,
			'page_capabilities' => \apply_filters( 'mss_index_page_capabilities', '', $post->ID, $blog_id ),
			'site_capabilities' => \apply_filters( 'mss_index_site_capabilities', '', $post->ID, $blog_id ),
			'meta'              => \apply_filters( 'mss_index_meta', '', $post->ID, $blog_id ),
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
			'%s',
			'%s',
		);

		if ( $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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

	/**
	 * Remove line feeds, spaces, etc.
	 *
	 * @param string $content The content.
	 * @param bool   $do_shortcodes Determine if we should expand shortcodes.
	 * @return string
	 */
	private function cleanup_content( $content, $do_shortcodes = true ) {

		if ( $do_shortcodes ) {
			$content = do_shortcode( $content );
		}

		// Strip tags.
		$content = wp_strip_all_tags( $content );

		// Cleanup spaces.
		$content = preg_replace( '/(\s+|(\&nbsp;)+)/m', ' ', $content );

		// Cleanup line feeds.
		$content = str_replace( "\n\n", "\n", $content );

		return $content;
	}

	/**
	 * Determines whether post is allowed to be indexed by cross-checking its post type.
	 *
	 * @param string|array $type The Post Type.
	 * @return bool
	 */
	private function do_index_post( $type ) {
		$post_types = apply_filters( 'mss_index_include_post_types', array( 'post', 'page' ) );

		return in_array( $type, $post_types, true );
	}

	/**
	 * Remove a post from the index.
	 * 
	 * @param int $post_id Post ID.
	 * 
	 * @return boolean $result.
	 */
	public function remove_post_from_index( $post_id ) {
		global $wpdb;

		$result = $wpdb->delete( $wpdb->multisite_search, array( 'post_id' => $post_id ) ); //phpcs:ignore

		return $result;
	}
}
