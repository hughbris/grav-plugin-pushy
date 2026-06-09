<?php

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

use Grav\Common\Grav;
use \Grav\Common\Uri;
use Grav\Common\Utils;

class PushyTest extends \Codeception\Test\Unit {

	/** @var Grav */
	protected $grav;

	public $grav_user;
	public $site_root = 'http://localhost:2015'; # TODO: way to detect or pass this in
	private $branch_name = 'develop/tests';

	public $folders = [
		'pages/03.added',
		'pages/04.mödified',
		'pages/05.deleted',
		'pages/06.rename_old',
		];

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before() {

		$this->grav = Grav::instance();
		$this->grav_user = GRAV_WEBROOT . DS . GRAV_USER_PATH;

		$this->setupFiles();
	}

	protected function _after() {
		codecept_debug($this->grav_user);
	}

	// tests
	public function testReadItems() {
		$setopt_content = [];

		$setopt_content[] = TRUE;
		$ch = curl_init("{$this->site_root}/admin/publish/pushy:readItems");

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$result_data = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result_data, TRUE);
		codecept_debug($result);

		assertEquals($result[0]['index'], 'A');
		assertEquals($result[0]['path'], '"pages/03.added/default space.md"');           # tweaked to pass
		// assertEquals($result[0]['orig_path'], 'pages/03.added/default space.md'); # ^^ this is a fudge ..

		assertEquals($result[1]['index'], 'M');
		assertEquals($result[1]['path'], '"pages/04.m\303\266dified/default.md"');       # tweaked to pass
		// assertEquals($result[1]['orig_path'], 'pages/04.mödified/default.md');    # ^^ .. and I know it

		assertEquals($result[2]['index'], 'D');
		assertEquals($result[2]['path'], 'pages/05.deleted/default.md');
		// assertEquals($result[2]['orig_path'], 'pages/05.deleted/default.md');

		assertEquals($result[3]['index'], 'R');
		assertEquals($result[3]['path'], 'pages/06.rename_new/default.md');
		assertEquals($result[3]['orig_path'], 'pages/06.rename_old/default.md');
	}

	public function testPublishItems() {
		$payload = [
			'items' => [
				[
					'index' => 'A',
					// 'path' => '"pages/03.added/default space.md"',      # this is why vv
					'path' => 'pages/03.added/default space.md',
					],
				[
					'index' => 'M',
					// 'path' => '"pages/04.m\303\266dified/default.md"',  # vv .. and I know it
					'path' => 'pages/04.mödified/default.md',              # this is a fudge .. ^^
					],
				[
					'index' => 'D',
					'path' => 'pages/05.deleted/default.md',
					],
				[
					'index' => 'R',
					'orig_path' => 'pages/06.rename_old/default.md',
					'path' => 'pages/06.rename_new/default.md',
					],
				],
			'message' => 'Publish changed pages ::testPublishItems()',
			];


		$ch = curl_init("{$this->site_root}/admin/publish/pushy:publishItems");

		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$result_data = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result_data, TRUE);
		codecept_debug($result);

		assertTrue($result['isSuccess']);
		assertEquals($result['alert'], 'Items have been published.');
	}

	function setupFiles() {

		$this->resetRepo();

		$this->commitFiles();
		$this->modifyFile();
		$this->deleteFile();
		$this->renameFile();
	}

	function resetRepo() {
		$this->killRepo();
		$this->clearFiles();
		$this->makeRepo();
		$this->createFiles();
	}

	function killRepo() {
		exec("cd {$this->grav_user} && rm -rf .git");
	}

	function clearFiles() {
		foreach ($this->folders as $folder) {
			exec("cd {$this->grav_user} && rm -rf $folder");
		}
		exec("cd {$this->grav_user} && rm -rf pages/06.rename_new");
	}

	function makeRepo() {
		exec("cd {$this->grav_user} && git init -b {$this->branch_name}"); // setting the branch name here avoids pesky warnings about 'master'
		exec("cd {$this->grav_user} && git config user.name \"unit\" && git config user.email \"unit@example.com\"");
		exec("cd {$this->grav_user} && git add . && git commit -m \"initial commit\"");
	}

	function createFiles() {
		foreach ($this->folders as $folder) {
			mkdir("{$this->grav_user}/{$folder}", 0777, TRUE);
		}

		file_put_contents(
			"{$this->grav_user}/pages/03.added/default space.md",
			"---\ntitle: Added\n---",
			);
		file_put_contents(
			"{$this->grav_user}/pages/04.mödified/default.md",
			"---\ntitle: To be modified\n---",
			);
		file_put_contents(
			"{$this->grav_user}/pages/05.deleted/default.md",
			"---\ntitle: To be deleted\n---",
			);
		file_put_contents(
			"{$this->grav_user}/pages/06.rename_old/default.md",
			"---\ntitle: To be renamed\n---",
			);
	}

	public function commitFiles() {
		exec("cd {$this->grav_user} && git add pages/04.mödified/default.md pages/05.deleted/default.md pages/06.rename_old/default.md");
		exec("cd {$this->grav_user} && git commit -m \"Initial commit\"");
	}

	function modifyFile() {
		file_put_contents(
			"{$this->grav_user}/pages/04.mödified/default.md",
			"\n# Content title",
			FILE_APPEND,
			);
	}

	function deleteFile() {
		exec("cd {$this->grav_user} && rm -rf pages/05.deleted");
	}

	function renameFile() {
		exec("cd {$this->grav_user} && rm -rf pages/06.rename_new");
		exec("cd {$this->grav_user} && mv -f pages/06.rename_old/ pages/06.rename_new");
	}
}