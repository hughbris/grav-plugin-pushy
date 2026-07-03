<?php

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Utils;
use Grav\Plugin\Pushy\PushyRepo;

class PushyRepoTest extends \Codeception\Test\Unit {
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var Grav */
	protected $grav;

	public $user_path;
	public $site_root = 'http://localhost:2015'; # TODO: find a way to detect or pass this in
	private $branch_name = 'develop/tests';

	public $folders = [
		'pages/PushyRepoTest/03.added',
		'pages/PushyRepoTest/04.mødified', // NB: special char
		'pages/PushyRepoTest/05.deleted',
		'pages/PushyRepoTest/06.rename_old',
		];

	protected $repo;

	protected function _before() {
		require_once(PLUGINS_DIR . 'pushy/vendor/autoload.php');
		$this->grav = Grav::instance();
		$this->user_path = GRAV_WEBROOT . DS . GRAV_USER_PATH;

		$this->setupFiles();
		$this->repo = new PushyRepo();
	}

	protected function _after() {
	}

	// tests
	public function testReadItems() {
		$setopt_content = [];

		$setopt_content[] = TRUE;
		$ch = curl_init("{$this->site_root}/admin/publish/pushy:readItems");

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$result_data = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		codecept_debug('Call to pushy:readItems ' . ($result_data ===  false ? 'FAILS!' : "returned status $status"));
		$result = json_decode($result_data, TRUE);
		codecept_debug($result);

		assertEquals($result[0]['index'], 'A');
		assertEquals($result[0]['path'], 'pages/PushyRepoTest/03.added/default space.md'); // NB: space in filename

		assertEquals($result[1]['index'], 'M');
		assertEquals($result[1]['path'], 'pages/PushyRepoTest/04.mødified/default.md');

		assertEquals($result[2]['index'], 'D');
		assertEquals($result[2]['path'], 'pages/PushyRepoTest/05.deleted/default.md');

		assertEquals($result[3]['index'], 'R');
		assertEquals($result[3]['path'], 'pages/PushyRepoTest/06.rename_new/default.md');
		assertEquals($result[3]['orig_path'], 'pages/PushyRepoTest/06.rename_old/default.md');
	}

	public function testPublishItems() {
		$payload = [
			'items' => [
				[
					'index' => 'A',
					'path' => 'pages/PushyRepoTest/03.added/default space.md',
					],
				[
					'index' => 'M',
					'path' => 'pages/PushyRepoTest/04.mødified/default.md',
					],
				[
					'index' => 'D',
					'path' => 'pages/PushyRepoTest/05.deleted/default.md',
					],
				[
					'index' => 'R',
					'orig_path' => 'pages/PushyRepoTest/06.rename_old/default.md',
					'path' => 'pages/PushyRepoTest/06.rename_new/default.md',
					],
				],
			'message' => 'Publish changed pages ::publishItems()',
			];

		$ch = curl_init("{$this->site_root}/admin/publish/pushy:publishItems");

		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$result_data = curl_exec($ch);
		curl_close($ch);

		codecept_debug($result_data ===  false ? 'Call to pushy:publishItems FAILS!' : $result_data );
		$result = json_decode($result_data, TRUE);

		assertTrue($result['isSuccess']);
		assertEquals($result['alert'], 'Items have been published.');
	}

	private function setupFiles() {

		$this->resetRepo();

		$this->commitFiles();
		$this->modifyFile();
		$this->deleteFile();
		$this->renameFile();
	}

	private function resetRepo() {
		$this->killRepo();
		$this->clearFiles();
		$this->makeRepo();
		$this->createFiles();
	}

	private function killRepo() {
		exec("cd {$this->user_path} && rm -rf .git");
	}

	private function clearFiles() {
		foreach ($this->folders as $folder) {
			exec("cd {$this->user_path} && rm -rf $folder");
		}
		exec("cd {$this->user_path} && rm -rf pages/PushyRepoTest/06.rename_new");
	}

	private function makeRepo() {
		exec("cd {$this->user_path} && git init -b {$this->branch_name}"); // setting the branch name here avoids pesky warnings about 'master'
		exec("cd {$this->user_path} && git config user.name \"unit\" && git config user.email \"unit@example.com\"");
		exec("cd {$this->user_path} && git add . && git commit -m \"initial commit\"");
	}

	private function createFiles() {
		foreach ($this->folders as $folder) {
			mkdir("{$this->user_path}/{$folder}", 0777, TRUE);
		}

		file_put_contents(
			"{$this->user_path}/pages/PushyRepoTest/03.added/default space.md",
			"---\ntitle: Added\n---",
			);
		file_put_contents(
			"{$this->user_path}/pages/PushyRepoTest/04.mødified/default.md",
			"---\ntitle: To be modified\n---",
			);
		file_put_contents(
			"{$this->user_path}/pages/PushyRepoTest/05.deleted/default.md",
			"---\ntitle: To be deleted\n---",
			);
		file_put_contents(
			"{$this->user_path}/pages/PushyRepoTest/06.rename_old/default.md",
			"---\ntitle: To be renamed\n---",
			);
	}

	private function commitFiles() {
		exec("cd {$this->user_path} && git add pages/PushyRepoTest/04.mødified/default.md pages/PushyRepoTest/05.deleted/default.md pages/PushyRepoTest/06.rename_old/default.md");
		exec("cd {$this->user_path} && git commit -m \"Initial commit\"");
	}

	private function modifyFile() {
		file_put_contents(
			"{$this->user_path}/pages/PushyRepoTest/04.mødified/default.md",
			"\n# Content title",
			FILE_APPEND,
			);
	}

	private function deleteFile() {
		exec("cd {$this->user_path} && rm -rf pages/PushyRepoTest/05.deleted");
	}

	private function renameFile() {
		exec("cd {$this->user_path} && rm -rf pages/PushyRepoTest/06.rename_new");
		exec("cd {$this->user_path} && mv -f pages/PushyRepoTest/06.rename_old/ pages/PushyRepoTest/06.rename_new");
	}

}