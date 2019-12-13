<?php
/**
 * Test_The_Plugin
 *
 * @package MultisiteSearch
 */

namespace MultisiteSearch;

/**
 * Class Test_The_Plugin
 *
 * @package MultisiteSearch
 */
class Test_The_Plugin extends \WP_UnitTestCase {
	/**
	 * Test _multisite_search_php_version_error().
	 *
	 * @see _multisite_search_php_version_error()
	 */
	public function test_multisite_search_php_version_error() {
		ob_start();
		_multisite_search_php_version_error();
		$buffer = ob_get_clean();
		$this->assertContains( '<div class="error">', $buffer );
	}

	/**
	 * Test _multisite_search_php_version_text().
	 *
	 * @see _multisite_search_php_version_text()
	 */
	public function test_multisite_search_php_version_text() {
		$this->assertContains( 'Multisite Search plugin error:', _multisite_search_php_version_text() );
	}
}
