<?php
/**
 * Multisite Search Queries.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Utility;

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

		$record_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"
                SELECT COUNT(*) FROM $wpdb->multisite_search
				WHERE MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE) AND (
                    page_capabilities = '' OR
                    page_capabilities REGEXP %s
                ) AND (
                    site_capabilities = '' OR
                    site_capabilities REGEXP %s
                )
                ",
				$keywords,
				$caps,
				$caps
			)
		);

		$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"
                SELECT *, SUM(MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE)) as score
                FROM $wpdb->multisite_search
                WHERE MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE) AND (
                    page_capabilities = '' OR
                    page_capabilities REGEXP %s
                )  AND (
                    site_capabilities = '' OR
                    site_capabilities REGEXP %s
                )
                GROUP BY blog_id, post_id, url, slug, post_title, post_content, page_capabilities, site_capabilities, meta, post_type
                ORDER BY score DESC
                LIMIT %d, %d;
                ",
				$keywords,
				$keywords,
				$caps,
				$caps,
				// $wpdb->prepare takes care of sanitization here.
				$args['page'] * $args['per_page'],
				$args['per_page']
			)
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
}
