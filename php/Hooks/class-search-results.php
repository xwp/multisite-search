<?php
/**
 * Hooks into search functionality to do multisite search results.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Hooks;

use MultisiteSearch\ComponentAbstract;

/**
 * Class Search Results
 */
class Search_Results extends ComponentAbstract {

	/**
	 * Register hooks for this view.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action(
			'template_include',
			function( $template ) {

				if ( is_admin() ) {
					return $template;
				}

				if ( ! empty( $_GET['search'] ) && ! empty( $_GET['_wpnonce'] ) && \wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ) ) {
					$results_template = \locate_template(
						array(
							'multisite-search-results.php',
						)
					);

					if ( $results_template ) {
						return $results_template;
					} else {
						return trailingslashit( __DIR__ ) . '../Templates/multisite-search-results.php';
					}
				}

				return $template;
			}
		);
	}
}
