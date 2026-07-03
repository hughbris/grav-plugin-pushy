<?php
namespace Grav\Plugin\Pushy;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use http\Exception\RuntimeException;
use GitElephant\Repository;
use GitElephant\Status\Status;
use Grav\Common\User\DataUser\User;

class PushyRepo extends Repository {

	/** @var Grav */
	protected $grav;

	/** @var array */
	protected $config, $configured_paths;

	public function __construct($path=NULL) {
		$path = $path ?? USER_DIR;
		$this->grav = Grav::instance();
		$this->setConfig($this->grav['config']->get('plugins.pushy'));

		parent::__construct($path);

		$this->addGlobalConfig('core.quotePath', 'false');
		$this->addGlobalConfig('i18n.commitEncoding', 'utf-8');
		$this->addGlobalConfig('i18n.logOutputEncoding', 'utf-8');
		$this->addGlobalConfig('status.showUntrackedFiles', 'all');
		$this->addGlobalConfig('status.renames', 'true'); // still doesn't work ...

		$this->configured_paths = $this->getConfig('folders');
	}

	/**
	 * @param array $config
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key=NULL) {
		if ($key) {
			return Utils::getDotNotation($this->config, $key);
		}
		// else ..
		return $this->config;
	}

	/**
	 * @return array
	 */
	public function getChangedItems(): Array {
        // $this->addGlobalCommandArgument('--update');
		foreach($this->configured_paths as $path) {
			$this->stage();
		}
		$statusItems = $this->statusParsed();
		$this->unstage(implode(' ', $this->configured_paths));

		return $statusItems;
	}

	/**
	 * parse changes into legacy array structure
	 * @return array
	 */
	private function statusParsed(): Array {

		$changes = $this->getPushyStatus($this->configured_paths)->all();
		$ret = [];

		foreach ($changes as $change) {
			$members = [
				'working' => $change->getWorkingTreeStatus() ?: ' ',
				'index' => $change->getIndexStatus() ?: ' ',
				];

			$members['path'] = $change->getName();

			if($change->isRenamed()) {
				$members['orig_path'] = $change->getName();
				$members['path'] = $change->getRenamed();
			}

			array_push($ret, $members);
		}
		return $ret;
	}

	/**
	 * extend getStatus() with extra command argument(s) for current command only, not "global"
	 */
	public function getPushyStatus($args): Status {
		// bit of a faff ...

		// stash a copy of any initial global arguments ..
		$globalArgumentsCache = $this->getGlobalCommandArguments();

		// add in any $args passed ..
		foreach($args as $arg) {
			$this->addGlobalCommandArgument($arg);
		}

		// stash the return status ..
		$ret = $this->getStatus();

		// purge the "global" command arguments ..
		foreach($this->getGlobalCommandArguments() as $arg) {
			$this->removeGlobalCommandArgument($arg);
		}

		// restore the stashed initial global arguments ..
		foreach($globalArgumentsCache as $arg) {
			$this->addGlobalCommandArgument($arg);
		}

		return $ret;
	}

	/**
	 * stage and commit selected paths with message
	 */
	public function publish(Array $items, String $message): VOID {
		// more faffing with "global" command arguments ... see:
		//  * https://github.com/matteosister/GitElephant/pull/67
		//  * https://github.com/matteosister/GitElephant/issues/122

		$restore = !in_array('--all', $this->getGlobalCommandArguments());

		$this->addGlobalCommandArgument('--all');

		foreach ($items as $item) {
			$this->stage($item['path']);
			if ($item['index'] === 'R') {
				$this->stage($item['orig_path']);
			}
		}

		if($restore) {
			$this->removeGlobalCommandArgument('--all');
		}

		$this->commit($message);

	}

}
