<?php
namespace Grav\Plugin\Pushy;

class GitService {
	private $repo;

	public function __construct($repo) {
		$this->repo = $repo;
	}

	public function respond($endpoint, $params): array {

		// TODO: crude auth here since it's not working from plugin, return 401

		switch($endpoint) {
			case 'stage':

				try {
					$unstaged = $this->repo->statusUnstaged();
					$this->repo->stageFiles();

					return [
						'status' => 200,
						'payload' => [
							'status' => 200,
							'endpoint' => $endpoint,
							'output' => $unstaged,
							'params' => $params,
							],
						];
				}
				catch (\Exception $e) {
					return [
						'status' => 500, // FIXME
						'payload' => [
							'status' => 500,
							'endpoint' => $endpoint,
							'unstaged' => $unstaged,
							],
						];
				}

			case 'commit':

				try {
					$this->repo->stageFiles();
					$command = $this->repo->commit($params['message']);
					return [
						'status' => 200,
						'payload' => [
							'status' => 200,
							'endpoint' => $endpoint,
							'command' => $command,
							],
						];
				}
				catch (\Exception $e) {
					return [
						'status' => 500, // FIXME
						'payload' => [
							'status' => 500,
							'endpoint' => $endpoint,
							'command' => $command,
							],
						];
				}
					
			default:
				return [
					'status' => 404,
					'payload' => [
						'status' => 404,
						'endpoint' => $endpoint,
						],
					];
		}

	}

}
