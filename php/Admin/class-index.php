<?php
/**
 * Multisite Search Index management.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Admin;

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

		\WP_CLI::success( "Indexing Site: $blog_id" );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				if ( $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->multisite_search,
					array(
						'blog_id'               => $blog_id,
						'post_id'               => $query->post->ID,
						'url'                   => $query->post->uuid,
						'slug'                  => $query->post->post_name,
						'post_title'            => get_the_title(),
						'post_content'          => str_replace( "\n\n", "\n", wp_strip_all_tags( do_shortcode( get_the_content() ) ) ),
						'required_capabilities' => '',
						'meta'                  => '',
					),
					array(
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				) ) {
					\WP_CLI::success( '.' );
				} else {
					$duplicate = strpos( $wpdb->last_error, 'Duplicate' ) !== false;
					if ( ! $duplicate ) {
						\WP_CLI::error( $wpdb->last_error );
					} else {
						// Todo: Add hash to update existing entries.
						\WP_CLI::success( 'Skipping duplicate entries.' );
					}
				}
			}
		}

		restore_current_blog();
	}
}
