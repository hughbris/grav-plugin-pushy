<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class PushyPlugin
 * @package Grav\Plugin
 */
class PushyPlugin extends Plugin {
	/** @var GitSync */ // FIXME
	protected $git;

	/** @var string */
	protected $admin_route = 'publish';

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array {
		return [
			'onPluginsInitialized' => [
				['onPluginsInitialized', 0],
				],
			];
	}

	/**
	 * Composer autoload
	 *
	 * @return ClassLoader
	 */
	/*
	public function autoload(): ClassLoader	{
		return require __DIR__ . '/vendor/autoload.php';
	}
	*/

	/**
	 * Initialize the plugin
	 */
	public function onPluginsInitialized(): void {
		// $this->init(); // TODO

		if ($this->isAdmin()) {
			return;
		}

		else {
			$this->enable([
				// Put your main events here
				]);
		}
	}
}
