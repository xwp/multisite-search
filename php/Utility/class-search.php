<?php
/**
 * Multisite Search Queries.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Utility;

use \InvalidArgumentException;

/**
 * Consumable Search object.
 */
class Search {

	/**
	 * Query the Multisite Search Index for results.
	 *
	 * @param string $keywords Keywords to search.
	 * @param array  $args Search options.
	 * @param string $caps Calculated user capabilities.
	 *
	 * @return mixed
	 */
	public function query(
		$keywords,
		$args = array(
			'per_page' => 0,
			'page'     => 0,
		),
		$caps = ''
	) {

		global $wpdb;

		$args['per_page'] = empty( $args['per_page'] ) ? apply_filters( 'mss_search_per_page', 10 ) : $args['per_page'];

		$user_id = \get_current_user_id();
		$caps    = empty( $caps ) ? \MultisiteSearch\Utility\User::get_capabilities() : $caps;

		/**
		 * We need at least an site admin cap if its empty to avoid protected content from showing.
		 * Passing an empty regex will mats ANYTHING, which breaks the protected content. Using
		 * `manage_sites` doesn't do anything except prevent REGEXP from doing what its not meant to.
		 */
		if ( empty( $caps ) ) {
			$caps = 'manage_sites';
		}

		/**
		 * Escalated users can bypass page and site capabilities.
		 */
		$escalated_caps = false;
		if ( ! empty( $user_id ) ) {
			$escalated_caps = \user_can( $user_id, 'manage_sites' ) || \user_can( $user_id, 'manage_options' );
		}
		$escalated_caps = apply_filters( 'mss_search_escalated_caps', $escalated_caps );

		// Convert capabilities for REGEXP.
		$caps     = '(' . str_replace( ',', '|', $caps ) . ')';
		$caps     = $escalated_caps ? '(.*)' : $caps;
		$keywords = str_replace( '**', ' ', $keywords );

		// List of post types to exclude from search.
		$exclude_post_types = apply_filters( 'mss_index_post_types', array( 'formation', 'formation_entry' ) );

		$priority_match = $wpdb->prepare(
			'MATCH (priority_keywords) AGAINST (%s)',
			$keywords
		);

		$content_match = $wpdb->prepare(
			'MATCH (post_title,post_content) AGAINST (%s)',
			$keywords
		);

		$capabilities_match = $wpdb->prepare(
			"
			(
				page_capabilities = '' OR
				page_capabilities REGEXP %s
			) AND (
				site_capabilities = '' OR
				site_capabilities REGEXP %s
			)
			",
			$caps,
			$caps
		);

		$post_type_match = $wpdb->prepare(
			'AND post_type NOT IN (' . $this->generate_dynamic_placeholders( $exclude_post_types, 'string' ) . ')',
			$exclude_post_types
		);

		$sites_match = null;
		$sites       = apply_filters( 'mss_search_blog_ids', array() );
		// Make sure they are all ints.
		$sites = array_map( 'intval', $sites );
		$sites = array_filter( $sites );
		if ( ! empty( $sites ) ) {
			$sites_match = $wpdb->prepare(
				'AND blog_id IN(' . implode( ', ', array_fill( 0, count( $sites ), '%d' ) ) . ')',
				$sites
			);
		}
		$count_q      = $wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->multisite_search " .
			// These were prepared above.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"WHERE ( $priority_match OR $content_match ) AND $capabilities_match $sites_match $post_type_match" .
			// This doesn't do anything, but need something that requires a placeholder
			// so that $wpdb::prepare() doesn't yell at us.
			'LIMIT %d, %d;',
			0,
			$args['per_page']
		);
		$record_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$count_q
		);
		$query        = $wpdb->prepare(
		// These were prepared above.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT *, SUM($priority_match OR $content_match) as score " .
			"FROM $wpdb->multisite_search " .
			// These were prepared above.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"WHERE ( $priority_match OR $content_match ) AND $capabilities_match $sites_match $post_type_match  " .
			'GROUP BY priority_keywords, blog_id, post_id, url, slug, post_title, post_content, page_capabilities, site_capabilities, meta, post_type
                ORDER BY priority_keywords DESC, score DESC
                LIMIT %d, %d;
                ',
			$args['page'] * $args['per_page'],
			$args['per_page']
		);
		$posts        = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query
		);

		return array(
			'query'   => array(
				'keywords' => $keywords,
				'per_page' => $args['per_page'],
				'page'     => $args['page'],
				'count'    => $record_count,
			),
			'entries' => $posts,
		);
	}

	/**
	 * Generate dynamic number of placeholders for $wpdb->prepare().
	 *
	 * @param array  $arr An array of values whose length
	 *                    will determine the number of placeholders to generate.
	 * @param string $type Must be either 'string', 'integer' or 'float.
	 *
	 * @return string $placeholders sprintf()-like placeholders
	 *
	 * @throws InvalidArgumentException Provided array was invalid.
	 * @throws InvalidArgumentException Provided placeholder type was invalid.
	 */
	public function generate_dynamic_placeholders( $arr, $type ) {

		if ( ! is_array( $arr ) || empty( $arr ) ) {
			throw new InvalidArgumentException( __( 'Invalid array of values.', 'multisite-search' ) );
		}

		switch ( $type ) {
			case 'string':
				$placeholder_type = '%s';
				break;

			case 'integer':
				$placeholder_type = '%d';
				break;

			case 'float':
				$placeholder_type = '%f';
				break;

			default:
				$placeholder_type = '';
				break;
		}

		if ( ! $type || ! $placeholder_type ) {
			throw new InvalidArgumentException( __( 'Invalid placeholder type.', 'multisite-search' ) );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $arr ), $placeholder_type ) );

		return $placeholders;
	}
}
