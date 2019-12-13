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
class Network_Admin_Menu extends ComponentAbstract {

	/**
	 * Register hooks for this view.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action(
			'network_admin_menu',
			function () {
				add_menu_page(
					'Multisite Search',
					'Multisite Search',
					'manage_options',
					'multisite-search-menu',
					array( $this, 'render' ),
					$this->plugin->get_assets_url( 'images/admin-menu-icon.svg' )
				);
			}
		);
		// Register other hooks here.
	}

	/**
	 * Render the Menu Page.
	 *
	 * @return void
	 */
	public function render() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Multisite Search Settings', 'multisite-search' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Settings here impact across the entire network.', 'multisite-search' ); ?></p>
		</div>
		<?php
	}
}
