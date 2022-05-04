<?php

namespace Grav\Plugin\Pushy;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use SebastianBergmann\Git\RuntimeException;

class GitUtils {

	/**
	 * Checks if the user/ folder is initialized as a Git repo
	 *
	 * @return bool
	 */
	public static function isGitInitialized() {
		return file_exists(rtrim(USER_DIR, '/') . '/.git');
	}

	/**
	 * @param bool $version
	 * @return bool|string
	 */
	public static function isGitInstalled($version = false) {
		$bin = static::getGitBinary();

		exec($bin . ' --version', $output, $returnValue);

		$installed = $returnValue === 0;

		if ($version && $output) {
			$output = explode(' ', array_shift($output));
			$versions = array_filter($output, static function($item) {
				return version_compare($item, '0.0.1', '>=');
			});

			$installed = array_shift($versions);
		}

		return $installed;
	}

	/**
	 * @param bool $override
	 * @return string
	 */
	public static function getGitBinary($override = false)
	{
		/** @var Config $grav */
		$config = Grav::instance()['config'];

		return $override ?: $config->get('plugins.pushy.git.bin', 'git');
	}

}
