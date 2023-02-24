<?php
namespace Grav\Plugin\Pushy;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use http\Exception\RuntimeException;
use CzProject\GitPhp\GitRepository;

class PushyRepo extends GitRepository {

	/** @var Grav */
	protected $grav;

	/** @var array */
	protected $config;

	public function __construct() {
		parent::__construct(USER_DIR, $this->runner);
		$this->grav = Grav::instance();
		$this->setConfig($this->grav['config']->get('plugins.pushy'));
	}

	/**
	 * @param array $config
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * Are there changes?
	 * `git status` + magic
	 * @return bool
	 * @throws GitException
	 */
	/* overloads \CzProject\GitPhp\GitRepository::hasChanges() which does not provide a pathspec argument */
	public function hasChanges($folders=[])	{
		// Make sure the `git status` gets a refreshed look at the working tree.
		$this->run('update-index', '-q', '--refresh');
		$result = $this->run('status', implode(' ', $folders), '--porcelain');
		return $result->hasOutput();
	}

	/**
	 * @return bool
	 */
	public function hasChangesToCommit() {
		return $this->hasChanges($this->config['folders']);
	}

	/**
	 * @return array
	 */
	private function statusLines($filter=TRUE) {
		$command = explode(' ', 'status -u --find-renames --porcelain');
		if ($filter) {
			$command = array_merge($command, $this->config['folders']);
		}
		return $this->execute($command);
	}

	/**
	 * @return string
	 */
	public static function listFiles($statusListing) {
		return implode(' ', array_column($statusListing, 'path'));
	}

	/**
	 * @return array
	 */
	public function statusParsed($filter=TRUE) {
		$changes = $this->statusLines($filter);
		$ret = [];

		foreach ($changes as $change) {
			$members = [
				'working' => substr($change, 1, 1),
				'index' => substr($change, 0, 1),
				];
			$paths = explode(' -> ', substr($change, 3));
			$members['path'] = array_shift($paths);
			if (!empty($paths)) {
				$members['orig_path'] = array_shift($paths);
			}

			array_push($ret, $members);
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	public function statusUnstaged($filter=TRUE) {
		return $this->statusSelect($filter);
	}

	/**
	 * @return array
	 */
	public function statusSelect($path_filter=TRUE, $env='working', $select='MTDRC?A') {
		$status = $this->statusParsed($path_filter);
		return array_values(array_filter($status, function($v) use ($env, $select) {
			return in_array($v[$env], str_split($select));
			}));
	}

	/**
	 * @return void
	 */
	public function stageFiles($statusListing=NULL) {
		if (is_null($statusListing)) {
			$files = self::listFiles($this->statusUnstaged());
		}
		else {
			$files = $statusListing;
		}
		if (!empty($files)) {
			$command = array_merge(['add', '--all'], explode(' ', $files));
			$this->execute($command);
		}
	}

	/**
	 * @param string $message
	 * @return string[]
	 */
	public function commit($message, $options = NULL) {

		if(!isset($this->grav['session'])) {
			return; // FIXME
		}

		// TODO: process placeholders/Twig in $message

		$user = $this->grav['session']->user->fullname; // TODO: add fallback as this is not required I think
		$email = $this->grav['session']->user->email;

		$author = $user . ' <' . $email . '>';
		$authorFlag = '--author="' . $author . '"';

		parent::commit($message, array_merge([$authorFlag], is_null($options) ? [] : $options));
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function getGitConfig($type, $default) {
		return $this->config['git'][$type] ?? $default;
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function getConfig($type, $value) {
		return $value ?: ($this->config[$type] ?? $value);
	}

	public static function combobulate($symbol) {
		// TODO: see if I can define symbols as 'Git' language words in translations, and translate them to English there
		// For now:
		$symbols = [
			'A' => 'New file',
			'M' => 'Modified',
			'R' => 'Renamed',
		];
		return $symbols($symbol);
	}
}
