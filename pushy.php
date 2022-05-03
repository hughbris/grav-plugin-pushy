<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;

/**
 * Class PushyPlugin
 * @package Grav\Plugin
 */
class PushyPlugin extends Plugin
{
	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onPluginsInitialized' => [
				['onPluginsInitialized', 0],
			]
		];
	}

	/**
	 * Composer autoload
	 *
	 * @return ClassLoader
	 */
	/*
	public function autoload(): ClassLoader
	{
		return require __DIR__ . '/vendor/autoload.php';
	}
	*/

	/**
	 * Initialize the plugin
	 */
	public function onPluginsInitialized(): void
	{
		// Don't proceed if we are in the admin plugin
		if ($this->isAdmin()) {
			return;
		}

		// Enable the main events we are interested in
		$this->enable([
			// Put your main events here
		]);
	}
}
