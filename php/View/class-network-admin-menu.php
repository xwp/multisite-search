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

	const MULTISITE_SEARCH_ADMIN_PAGE = 'multisite-search-menu';
	const MULTISITE_SEARCH_SETTINGS   = 'multisite_search_settings';

	/**
	 * Sections for settings.
	 *
	 * @var array Sections.
	 */
	public $sections = array();

	/**
	 * Fields for settings.
	 *
	 * @var array Fields.
	 */
	public $fields = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		$primary_site = '';

		if ( defined( 'SITE_ID_CURRENT_SITE' ) && 0 < (int) SITE_ID_CURRENT_SITE ) {
			$blog_details = \get_blog_details( SITE_ID_CURRENT_SITE );
			$primary_site = $blog_details->blogname;
		}

		// Settings sections.
		$this->sections = array(
			self::MULTISITE_SEARCH_SETTINGS => array(
				'label'       => __( 'General Settings', 'multisite-search' ),
				'description' => __( 'Generic Multisite Search settings that apply across the multisite network.', 'multisite-search' ),
			),
		);

		// Settings fields.
		$this->fields = array(
			self::MULTISITE_SEARCH_SETTINGS => array(
				'index_primary' => array(
					'label'       => __( 'Index primary site in network.', 'multisite-search' ),
					// Translators: %s is the name of the primary site.
					'description' => sprintf( __( 'Check to also the primary site (%s).', 'multisite-search' ), $primary_site ),
					'type'        => 'checkbox',
					'default'     => 0,
				),
			),
		);
	}

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
					self::MULTISITE_SEARCH_ADMIN_PAGE,
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

		$this->update_network_options();
		$multisite_search_options = \get_site_option( self::MULTISITE_SEARCH_SETTINGS, false );
		$is_configured            = ! empty( $multisite_search_options );

		?>
		<div class="wrap">
			<h2><?php echo esc_html( \get_admin_page_title() ); ?></h2>
			<form method="post">
				<!-- Add WP nonce, because we're not using Settings API. -->
				<?php wp_nonce_field( 'update_multisite_search_settings', '_wpnonce' ); ?>
				<!-- Render Settings Sections. -->
				<?php $this->render_settings( $this->sections, $this->fields, $multisite_search_options ); ?>
				<!-- Render Submit. -->
				<?php \submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Settings.
	 *
	 * @param array $sections The sections.
	 * @param array $fields The fields.
	 * @param array $options The options from the database.
	 * @return void
	 */
	public function render_settings( $sections, $fields, $options ) {
		foreach ( $sections as $key => $section ) {
			?>
			<h2><?php echo esc_html( $section['label'] ); ?></h2>

			<?php if ( ! empty( $section['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
			<?php endif; ?>

			<?php
			$this->render_fields( $fields[ $key ], $options );
		}
	}

	/**
	 * Render Fields.
	 *
	 * @param array $fields The fields.
	 * @param array $options Passed in options.
	 * @return void
	 */
	public function render_fields( $fields, $options ) {

		$options = empty( $options ) ? array() : $options;
		$options = apply_filters( 'mss_network_settings', $options );

		?>
		<table class="form-table">
		<tbody>
		<?php

		// Get the field types and iterate. Only support the fields we need.
		foreach ( $fields as $key => $field ) {
			switch ( $field['type'] ) {
				case 'password':
				case 'text':
					?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<input id="<?php echo esc_attr( $key ); ?>"
									name="<?php echo esc_attr( $key ); ?>"
									class="regular-text"
									type="<?php echo esc_attr( $field['type'] ); ?>"
									placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
									value="<?php echo esc_attr( $options[ $key ] ); ?>" />
								<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
							</td>
						</tr>
					<?php
					break;
				case 'checkbox':
					$field_value = isset( $options[ $key ] ) ? (int) $options[ $key ] : (int) $field['default'];
					?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<input id="<?php echo esc_attr( $key ); ?>"
									name="<?php echo esc_attr( $key ); ?>"
									type="<?php echo esc_attr( $field['type'] ); ?>"
									value="1"
									<?php checked( $field_value, 1 ); ?> />
								<span class="description"><?php echo esc_html( $field['description'] ); ?></span>
							</td>
						</tr>
					<?php
					break;
				default:
					break;
			}
		}

		?>
		</tbody></table>
		<?php
	}

	/**
	 * Update network options.
	 *
	 * Effects ALL sites in the multisite network.
	 *
	 * @return void
	 */
	public function update_network_options() {

		// If a form has been posted, make sure its the form we expected.
		if ( isset( $_POST['submit'] ) && \check_admin_referer( 'update_multisite_search_settings' ) ) {

			$post_vars = array_map( 'wp_unslash', $_POST );

			$multisite_search_options = array(
				'index_primary' => isset( $post_vars['index_primary'] ) ? (int) $post_vars['index_primary'] : 0,
			);

			update_site_option( self::MULTISITE_SEARCH_SETTINGS, $multisite_search_options );
		}
	}
}
