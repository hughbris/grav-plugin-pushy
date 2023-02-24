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

	/** @var string */
	protected $repositoryPath;

	public function __construct() {
		$this->grav = Grav::instance();
		$this->config = $this->grav['config']->get('plugins.pushy');
		$this->repositoryPath = USER_DIR;



		parent::__construct($this->repositoryPath, $this->runner);
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
	// adapted from \CzProject\GitPhp\GitRepository::hasChanges() but that does not provide a pathspec argument
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
			$files = self::listFiles($this->statusUnstaged());
		}
		else {
			$files = $statusListing;
		}
		if (!empty($files)) {
			$command = 'add --all';
			$this->execute("$command $files");
		}
	}

	/**
	 * @param string $message
	 * @return string[]
	 */
	public function commit($message) {

		if(!isset($this->grav['session'])) {
			return; // FIXME
		}

		// TODO: process placeholders/Twig in $message

		$user = $this->grav['session']->user->fullname; // TODO: add fallback as this is not required I think
		$email = $this->grav['session']->user->email;

		$author = $user . ' <' . $email . '>';
		$authorFlag = '--author="' . $author . '"';

		return $this->execute("commit $authorFlag -m " . escapeshellarg($message));
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
