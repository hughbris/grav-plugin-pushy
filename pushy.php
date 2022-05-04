<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\Pushy\PushyRepo;
use Grav\Plugin\Pushy\GitUtils;

/**
 * Class PushyPlugin
 * @package Grav\Plugin
 */
class PushyPlugin extends Plugin {
	/** @var PushyRepo */
	protected $repo;

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
	public function autoload(): ClassLoader	{
		return require __DIR__ . '/vendor/autoload.php';
	}

	/**
	 * Initialize the class instance
	 */
	public function init():void {
		$this->repo = new PushyRepo();
	}

	/**
	 * Initialize the plugin
	 */
	public function onPluginsInitialized(): void {
		$this->init();

		if ($this->isAdmin()) {
			$this->enable([
				'onTwigSiteVariables' => ['routePages', 0],
				'onAdminMenu' => ['showPublishingMenu', 0],
				'onAdminTwigTemplatePaths'  => ['setAdminTwigTemplatePaths', 0],
				]);
		}

		else {
			$this->enable([
				// Put your main events here
				]);
		}
	}

	/**
	 * Get admin page template
	 */
	public function setAdminTwigTemplatePaths(Event $event): void {
		$paths = $event['paths'];
		$paths[] = __DIR__ . DS . 'admin/templates';
		$event['paths'] = $paths;
	}

	/**
	 * Show the publishing menu item(s) in Admin
	 */
	public function showPublishingMenu(): void {
		$isInitialized = GitUtils::isGitInitialized();
		// TODO: test for GitUtils::isGitInstalled()
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

    public function routePages($event): void { // TODO: stub
		$publish_path = $this->config->get('plugins.admin.route') . DS . $this->admin_route;
		$route = $this->grav['uri']->path();

		if ($route == $publish_path) {

			$page = new Page();
			$page->init(new \SplFileInfo(__DIR__ . '/admin/pages/publish.md'));

			/** @var Pages */
			$pages = $this->grav['pages'];
			$pages->addPage($page);
			unset($this->grav['page']);
			$this->grav['page'] = $page;

			$twig = $this->grav['twig'];
			$twig->twig_vars['git_index'] = $this->repo->statusSelect(); # TRUE, $env='index', $select='MTDRCA');
		}
	}

}
