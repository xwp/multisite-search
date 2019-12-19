<?php
/**
 * Network_Admin_Menu.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\View;

use MultisiteSearch\ComponentAbstract;

/**
 * Class Network_Admin_Menu
 */
class Search_Box extends ComponentAbstract {

	/**
	 * Register hooks for this view.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action(
			'mss_search_box',
			function () {

				$template = \locate_template(
					array(
						'multisite-search-form.php',
					)
				);

				if ( $template ) {
					require_once $template;
				} else {
					require_once trailingslashit( __DIR__ ) . '../Templates/multisite-search-form.php';
				}
			}
		);
	}
}
