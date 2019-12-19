<?php
/**
 * Search API.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\API;

use MultisiteSearch\ComponentAbstract;

/**
 * Class Search
 */
class Search extends ComponentAbstract {

	/**
	 * Version for these endpoints.
	 *
	 * @var int
	 */
	private $version = 'v1';

	/**
	 * Register all the hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Init search routes.
	 *
	 * @return void
	 */
	public function rest_api_init() {
		$base = $this->plugin->get_slug() . "/{$this->version}";

		// Prepare endpoint with current user's non-core capabilities.
		$user_caps = \MultisiteSearch\Utility\User::get_capabilities();

		register_rest_route(
			$base,
			'search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_search_request' ),
				'args'                => array(
					'user_caps' => $user_caps,
				),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Return search results.
	 *
	 * @param \WP_REST_Request $r The request.
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function handle_search_request( \WP_REST_Request $r ) {
		$args = array(
			'per_page' => ! empty( $r->get_param( 'per_page' ) ) ? $r->get_param( 'per_page' ) : apply_filters( 'mss_search_per_page', 10 ),
			'page'     => ! empty( $r->get_param( 'page' ) ) ? $r->get_param( 'page' ) : 0,
			'q'        => $r->get_param( 'q' ),
		);

		$keywords = ! empty( $r->get_param( 'q' ) ) ? $r->get_param( 'q' ) : '';

		$search  = new \MultisiteSearch\Utility\Search();
		$results = $search->query( $keywords, $args, $r->get_attributes()['args']['user_caps'] );

		$results = \apply_filters( 'mss_search_results', $results, $keywords, $args );

		return rest_ensure_response( $results );
	}
}
