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
	 *
	 * @return mixed
	 */
	public function query( $keywords, $args = array(
		'per_page' => 100,
		'page'     => 0,
	) ) {

		global $wpdb;

		// Make terms a bit more fuzzy.
		$keywords = implode(
			' ',
			array_map(
				function( $keyword ) {
					return "*$keyword*";
				},
				explode( ' ', $keywords )
			)
		);

		$record_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"
                SELECT COUNT(*) FROM $wpdb->multisite_search
                WHERE MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE)
                ",
				$keywords
			)
		);

		$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"
                SELECT *, SUM(MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE)) as score
                FROM $wpdb->multisite_search
                WHERE MATCH (post_title,post_content) AGAINST (%s IN BOOLEAN MODE)
                GROUP BY blog_id, post_id, url, slug, post_title, post_content, required_capabilities, meta, post_type
                ORDER BY score DESC
                LIMIT %d, %d;
                ",
				$keywords,
				$keywords,
				// $wpdb->prepare takes care of sanitization here.
				$args['page'] * $args['per_page'],
				$args['per_page']
			)
		);

		// Make terms a bit less fuzzy.
		$keywords = implode(
			' ',
			array_map(
				function( $keyword ) {
					return trim( $keyword, '*' );
				},
				explode( ' ', $keywords )
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
