<?php

namespace Grav\Plugin\Pushy;

use Grav\Common\Grav;

class Helpers {

	static function translate(string $key, ?string ...$args) : string {
		$prefix = 'PLUGIN_PUSHY';
		$grav = Grav::instance();

		$user = $grav['user'];
        $language = $user['language'];

		$translation = $grav['language']->translate(["$prefix.$key", ...$args], [$language]);

		$untranslated = ($translation == "$prefix.$key");
		if ($untranslated) {
			$translation = $grav['language']->translate(["$prefix.$key", ...$args], ['en']);
		}

		return $translation;
	}

}