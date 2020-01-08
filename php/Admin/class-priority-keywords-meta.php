<?php
/**
 * Multisite Search Admin View for Priority Keywords.
 *
 * @package   MultisiteSearch
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch\Admin;

use MultisiteSearch\ComponentAbstract;

/**
 * Adds new meta and features for priority keyword search/indexing.
 */
class Priority_Keywords_Meta extends ComponentAbstract {

	/**
	 * Register hooks for this priority keywords.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_post_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register post meta fields.
	 *
	 * @return void
	 */
	public function register_post_meta() {
		\register_post_meta(
			'',
			'mss_priority_keywords',
			array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			)
		);
	}

	/**
	 * Enqueue Multisite Search panel.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'mss-priority-keywords',
			$this->plugin->get_url( 'js/blocks/priority-keywords.js' ),
			array( 'wp-blocks', 'wp-element', 'wp-components' ),
			filemtime( $this->plugin->get_path( 'js/blocks/priority-keywords.js' ) ),
			true
		);
	}

}
