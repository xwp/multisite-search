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

		register_rest_route(
			$base,
			'search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_search_request' ),
				'args'                => array(),
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

		$content = array(
			'a' => 'AA',
			'b' => 'BB',
			'q' => $r->get_param( 'q' ),
		);

		return rest_ensure_response( $content );
	}

}
