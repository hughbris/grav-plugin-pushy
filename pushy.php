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
			$this->enable([
				'onAdminMenu' => ['showPublishingMenu', 0],
				]);
		}

		else {
			$this->enable([
				// Put your main events here
				]);
		}
	}

	/**
	 * Show the publishing menu item(s) in Admin
	 */
	public function showPublishingMenu(): void {
		$isInitialized = $this->isGitInitialized();
		// TODO: test for Helper::isGitInstalled()
		$menuLabel = $isInitialized ? 'Publish' : 'Publishing';
		$options = [
			'hint' => $isInitialized ? 'Publish' : 'Publication settings',
			'location' => 'pages',
			'route' => $isInitialized ? $this->admin_route : "plugins/{$this->name}",
			'icon' => 'fa-' . ($isInitialized ? $this->grav['plugins']->get($this->name)->blueprints()->get('icon') : 'cog'),
			// 'class' => '',
			// 'data' => [],
			];

		$this->grav['twig']->plugins_hooked_nav[$menuLabel] = $options; // TODO: make this configurable in YAML/blueprint
	}

	/**
	 * Checks if the user/ folder is initialized as a Git repo
	 *
	 * @return bool
	 */
	// adapted/copied from GitSync Helper::isGitInitialized()
	// TODO: move this to git class when I have one and rename
	public static function isGitInitialized() {
		return file_exists(rtrim(USER_DIR, '/') . '/.git');
	}

}
