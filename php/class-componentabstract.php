<?php
/**
 * Component abstract.
 *
 * @package   MultisiteSearch
 * @copyright Copyright(c) 2019, Rheinard Korf
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 */

namespace MultisiteSearch;

/**
 * Class ComponentAbstract
 */
abstract class ComponentAbstract implements ComponentInterface {

	/**
	 * Point to the $plugin instance.
	 *
	 * @var PluginInterface
	 */
	protected $plugin;

	/**
	 * Set the plugin so that it can be referenced later.
	 *
	 * @param PluginInterface $plugin The plugin.
	 *
	 * @return ComponentInterface $this
	 */
	public function set_plugin( PluginInterface $plugin ) {
		$this->plugin = $plugin;
		return $this;
	}

	/**
	 * Register any hooks that this component needs.
	 *
	 * @return void
	 */
	abstract public function register_hooks();
}
