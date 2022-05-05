<?php
namespace Grav\Plugin\Pushy;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Utils;
use http\Exception\RuntimeException;
use SebastianBergmann\Git\Git;

class PushyRepo extends Git {

	/** @var Grav */
	protected $grav;

	/** @var array */
	protected $config;

	/** @var string */
	protected $repositoryPath;

	public function __construct() {
		$this->grav = Grav::instance();
		$this->config = $this->grav['config']->get('plugins.pushy');
		$this->repositoryPath = USER_DIR;

		parent::__construct($this->repositoryPath);

	}

	/**
	 * @param array $config
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * @return bool
	 */
	public function hasChangesToCommit() {
		$folders = $this->config['folders'];
		$message = 'nothing to commit';
		$output = $this->execute('status ' . implode(' ', $this->config['folders'])); // TODO: make space-separated folder list a static function

		return strpos($output[count($output) - 1], $message) !== 0;
	}

	/**
	 * @return array
	 */
	private function statusLines($filter=TRUE) {
		$command = 'status -u --find-renames --porcelain';
		if ($filter) {
			$command .= ' ' . implode(' ', $this->config['folders']);
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
			$files = '.';
		}
		else {
			$files = self::listFiles($this->statusUnstaged());
		}
		$command = 'add --all';
		$this->execute("$command $files");
	}

	/**
	 * @param string $command
	 * @param bool $quiet
	 * @return string[]
	 */
	public function execute($command, $quiet=FALSE) {
		try {
			$bin = GitUtils::getGitBinary($this->getGitConfig('bin', 'git'));
			/** @var string $version */
			$version = GitUtils::isGitInstalled(true);

			// -C <path> supported from 1.8.5 and above
			if (version_compare($version, '1.8.5', '>=')) {
				$command = $bin . ' -C ' . escapeshellarg($this->repositoryPath) . ' ' . $command;
			}
			else {
				$command = 'cd ' . $this->repositoryPath . ' && ' . $bin . ' ' . $command;
			}

			$command .= ' 2>&1';

			if (DIRECTORY_SEPARATOR === '/') {
				$command = 'LC_ALL=C ' . $command;
			}

			if ($this->getConfig('logging', false)) {
				$this->grav['log']->notice('pushy[command]: ' . $command);
				exec($command, $output, $returnValue);
				$output_string = is_array($output) ? json_encode($output) : $output;
				$this->grav['log']->notice('pushy[output]: ' . $output_string);
			}
			else {
				exec($command, $output, $returnValue);
			}

			if ($returnValue !== 0 && $returnValue !== 5 && !$quiet) {
				throw new \RuntimeException(implode("\r\n", $output));
			}

			return $output;
		}
		catch (\RuntimeException $e) {
			$message = $e->getMessage();

			// handle scary messages - TODO?
			/*
			if (Utils::contains($message, 'some scary part of a message')) {
				$message = 'BLABLA';
			}
			*/

			throw new \RuntimeException($message);
		}
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
