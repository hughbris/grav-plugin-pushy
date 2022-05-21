<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Exception;
use Grav\Common\Assets;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Uri;
use Grav\Framework\DI\Container;
use Grav\Plugin\Pushy\RequestHandler;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\Pushy\PushyRepo;
use Grav\Plugin\Pushy\GitUtils;

/**
 * Class PushyPlugin
 * @package Grav\Plugin
 */
class PushyPlugin extends Plugin
{
	/** @var PushyRepo */
	protected $repo;

	/** @var string */
	protected $admin_route = 'publish';

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
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
	public function autoload(): ClassLoader
	{
		return require __DIR__ . '/vendor/autoload.php';
	}

	/**
	 * Initialize the class instance
	 */
	public function init(): void
	{
		$this->repo = new PushyRepo();
	}

	/**
	 * Initialize the plugin
	 */
	public function onPluginsInitialized(): void
	{
		$this->init();

		if ($this->isAdmin()) {
			if ($this->isOnRoute()) {
				/** @var RequestHandler */
				$requestHandler = new RequestHandler();
				$response = $requestHandler->handleRequest();

				if ($response) {
					echo json_encode($response);
					die();
				}
			}

			$this->enable([
				'onAdminTwigTemplatePaths'  => ['setAdminTwigTemplatePaths', 0],
				'onAdminMenu' => ['showPublishingMenu', 0],
				'onTwigSiteVariables' => ['setTwigSiteVariables', 0],
				'onAssetsInitialized' => ['onAssetsInitialized', 0],
			]);
		} else {
			$this->enable([
				'onPageInitialized' => ['serveHooks', 0],
				]);
		}
	}

	/**
	 * Get admin page template
	 */
	public function setAdminTwigTemplatePaths(Event $event): void
	{
		$paths = $event['paths'];
		$paths[] = __DIR__ . DS . 'admin/templates';
		$event['paths'] = $paths;
	}

	/**
	 * Show the publishing menu item(s) in Admin
	 */
	public function showPublishingMenu(): void
	{
		$isInitialized = GitUtils::isGitInitialized();
		// TODO: test for GitUtils::isGitInstalled()
		$menuLabel = $isInitialized ? 'Publish' : 'Publishing';

		$count = new Container(['count' => function () { 
			$itemCount = count($this->repo->statusSelect());

			return $itemCount === 0 ? '' : $itemCount; 
		}]);

		$options = [
			'hint' => $isInitialized ? 'Publish' : 'Publication settings',
			'location' => 'pages',
			'route' => $isInitialized ? $this->admin_route : "plugins/{$this->name}",
			'icon' => 'fa-' . ($isInitialized ? $this->grav['plugins']->get($this->name)->blueprints()->get('icon') : 'cog'),
			'badge' => $count,

			// 'class' => '',
			// 'data' => [],
		];

		$this->grav['twig']->plugins_hooked_nav[$menuLabel] = $options; // TODO: make this configurable in YAML/blueprint
	}

	/**
	 * Set any special variables for Twig templates
	 */
	public function setTwigSiteVariables(): void
	{
		$publish_path = $this->config->get('plugins.admin.route') . DS . $this->admin_route;
		$route = $this->grav['uri']->path();

		$isInitialized = GitUtils::isGitInitialized();
		// TODO: test for GitUtils::isGitInstalled() - make a wrapper for these two funcs since we're checking them twice now

		if ($isInitialized && $route == $publish_path) {
			$twig = $this->grav['twig'];
			$twig->twig_vars['git_index'] = $this->repo->statusSelect(); # TRUE, $env='index', $select='MTDRCA');
		}
	}

	public function serveHooks(): void {

		$webhooks = $this->config->get("plugins.{$this->name}.webhooks");
		if (!($webhooks['enabled'] ?? FALSE)) {
			return;
		}
		$page = $this->grav['page'] ?? NULL;

		if (strpos($this->grav['uri']->uri(), $webhooks['path']) === 0) {

			if ($webhooks['secret'] ?? FALSE) {
				if (!self::isWebhookAuthenticated($webhooks['secret'])) { // authentication fails
					self::jsonRespond(401, [
						'status' => 'error',
						'message' => 'Unauthorized request',
					]);
				}
			}

			if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
				self::jsonRespond(405, [
					'status' => 'error',
					'message' => 'Only POST operations supported',
				]);
			}

			$endpoints = $webhooks['endpoints'] ?? [];

			// check if the request path is an exact match with the webhook root path
			if($this->grav['uri']->uri() == $webhooks['path']) {
				self::jsonRespond(300, [
					'status' => 'info',
					'message' => ('Available endpoints are: ' . implode(', ', array_keys($endpoints))),
				]);
			}

			foreach ($endpoints as $hook => $hook_properties) {

				// match on the endpoint
				$endpoint = strtolower(implode('/', [$webhooks['path'], $hook]));
				if (strtolower($this->grav['uri']->uri()) ==  $endpoint) {

					// check for declared hook response action
					if (!$hook_properties || !array_key_exists('run', $hook_properties)) {
						self::jsonRespond(418, [
							'status' => 'undefined',
							'message' => 'Am teapot, no operation specified or performed',
							// 'debug' => $hook_properties,
						]);
					}

					// let's grab that payload
					$payload = file_get_contents('php://input');
					$payload = !empty($payload) ? json_decode($payload) : FALSE;

					if(!$payload) {
						self::jsonRespond(400, [
							'status' => 'undefined',
							'message' => 'No payload or invalid payload',
							// 'debug' => $hook_properties,
						]);
					}

					// check declared conditions
					if (array_key_exists('conditions', $hook_properties)) {

						$conditions = $hook_properties['conditions'];

						if(array_key_exists('branch', $conditions) && ($this->parsePayload($payload, 'branch') !== $conditions['branch'])) {
							self::jsonRespond(422, [ // FIXME: 422 not sure
								'status' => 'undefined',
								'message' => 'Branch constraint not met',
								// 'debug' => $hook_properties,
							]);
						}

						if(array_key_exists('committer', $conditions) && ($this->parsePayload($payload, 'committer') !== $conditions['committer'])) {
							self::jsonRespond(422, [ // FIXME: 422 not sure
								'status' => 'undefined',
								'message' => 'Committer constraint not met',
								// 'debug' => $this->parsePayload($payload, 'committer'),
							]);
						}
					}

					try {
						// perform the named scheduled task
						$action = $hook_properties['run'];
						$result = self::triggerSchedulerJob($action);

						if($result) {
							self::jsonRespond(200, [
								'status' => 'success',
								'message' => "Operation succeeded: '$action'",
								// 'debug' => $hook_properties,
							]);
						}
					}
					catch (\Exception $e) {
						self::jsonRespond(500, [
							'status' => 'error',
							'message' => "Operation failed: '$action' with \"{$e->getMessage()}\"",
							// 'debug' => $hook_properties,
						]);
					}
				}
			}

			// 404 fallback for endpoints under webhooks path, happens anyway I think but this sets useful JSON body
			self::jsonRespond(404, [
				'status' => 'error',
				'message' => 'Endpoint not found',
				// 'debug' => $webhooks,
			]);
		}
	}

	/**
	 * Perform a Grav Scheduler task, specified by name
	 * @param  string $job_name The name (ID) of the task to run
	 * @return bool             true if the job was found and ran successfully, otherwise throws \Exception
	 */
	private static function triggerSchedulerJob($job_name): bool
	{
		$scheduler = new \Grav\Common\Scheduler\Scheduler();
		$job = $scheduler->getJob($job_name);
		if ($job) {
			$job->inForeground()->run();
			if (!$job->isSuccessful()) { // was unsuccessful
				throw new \Exception($job->getOutput());
			}
		} else {
			throw new \Exception('job not defined');
		}
		return TRUE; // FIXME: return the job instead, since we can then access its properties and methods from the caller
	}

	// TODO: move these into GitUtils I think ..
	/**
	 * Returns true if the request contains a valid signature or token
	 * @param  string $secret local secret
	 * @return bool           whether or not the request is authorized
	 */
	// copied from GitSync base class method isRequestAuthorized()
	private static function isWebhookAuthenticated($secret): bool
	{
		if (isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
			$payload = file_get_contents('php://input') ?: '';

			return self::isGithubSignatureValid($secret, $_SERVER['HTTP_X_HUB_SIGNATURE'], $payload);
		}

		if (isset($_SERVER['HTTP_X_GITLAB_TOKEN'])) {
			return self::isGitlabTokenValid($secret, $_SERVER['HTTP_X_GITLAB_TOKEN']);
		} else {
			$payload = file_get_contents('php://input');
			return self::isGiteaSecretValid($secret, $payload);
		}

		return FALSE;
	}

	/**
	 * Hashes the webhook request body with the client secret and checks if it matches the webhook signature header
	 * @param  string $secret The webhook secret
	 * @param  string $signatureHeader The signature of the webhook request
	 * @param  string $payload The webhook request body
	 * @return bool            whether the signature is valid or not
	 */
	// copied from GitSync base class method but uses more secure hash_equals()
	private static function isGithubSignatureValid($secret, $signatureHeader, $payload): bool
	{
		[$algorithm, $signature] = explode('=', $signatureHeader);

		return hash_equals($signature, hash_hmac($algorithm, $payload, $secret));
	}

	/**
	 * Returns true if given Gitlab token matches secret
	 * @param  string $secret local secret
	 * @param  string $token token received from Gitlab webhook request
	 * @return bool          whether or not secret and token match
	 */
	// copied from GitSync base class method but uses more secure hash_equals()
	// TODO: untested
	private static function isGitlabTokenValid($secret, $token): bool
	{
		return hash_equals($secret, $token);
	}

	/**
	 * Returns true if secret contained in the payload matches the client secret
	 * @param  string $secret The webhook secret
	 * @param  string $payload The webhook request body
	 * @return bool            whether the client secret matches the payload secret or not
	 */
	// copied from GitSync base class method but uses more secure hash_equals()
	// TODO: untested
	private static function isGiteaSecretValid($secret, $payload): bool
	{
		$payload = json_decode($payload, TRUE);
		if (!empty($payload) && isset($payload['secret'])) {
			return hash_equals($secret, $payload['secret']);
		}
		return FALSE;
	}

	// TODO: this can be static
	/**
	 * Provide a HTTP status and JSON response and exit
	 * @param  int    $http_status   HTTP status number to return
	 * @param  array  $proto_payload Payload as array to be served as JSON
	 * @return void
	 */
	private static function jsonRespond(int $http_status, array $proto_payload): void {
		header('Content-Type: application/json');
		http_response_code($http_status);
		echo json_encode($proto_payload);
		exit;
	}

	// TODO: this can be static
	/**
	 * Parse JSON payloads and extract key properties
	 * @param  object $payload       JSON-decoded payload string
	 * @param  string $component     Enumerated standard element name to be extracted
	 * @return mixed
	 */
	private function parsePayload($payload, $component)
	{
		switch ($component) {
			case 'branch':
				if (property_exists($payload, 'ref')) {
					return substr($payload->ref, strlen('refs/heads/'));
				}
			case 'committer':
				if (property_exists($payload, 'pusher') && property_exists($payload->pusher, 'email')) {
					return $payload->pusher->email;
				}
		}
		// fallback
		return NULL;
	}

	/**
	 * Add requied JavaScript and CSS to page
	 */
	public function onAssetsInitialized(): void
	{
		if (!$this->isOnRoute()) {
			return;
		}

		/** @var Assets */
		$assets = $this->grav['assets'];

		$assets->addJs("plugin://pushy/js/pushy-admin.js", ['type' => 'module']);
		$assets->addCss("plugin://pushy/css/pushy-admin.css");
	}

	/**
	 * Check if the request is made by the Pushy plugin.
	 * 
	 * @return bool
	 */
	protected function isOnRoute(): bool
	{
		Assert($this->config !== null, 'Config has not been initialized');

		$currentPath = $this->grav['uri']->path();
		$publishPath = $this->config->get('plugins.admin.route') . "/$this->admin_route";

		return $currentPath === $publishPath;
	}
}
