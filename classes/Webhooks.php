<?php

namespace Grav\Plugin\Pushy;

class Webhooks {

	/**
	 * Returns true if the request contains a valid signature or token
	 * @param  string $secret local secret
	 * @return bool           whether or not the request is authorized
	 */
	// copied and adapted from GitSync base class method isRequestAuthorized()
	public static function isAuthenticated($secret): bool {
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
	private static function isGithubSignatureValid($secret, $signatureHeader, $payload): bool {
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
	private static function isGitlabTokenValid($secret, $token): bool {
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
	private static function isGiteaSecretValid($secret, $payload): bool {
		$payload = json_decode($payload, TRUE);
		if (!empty($payload) && isset($payload['secret'])) {
			return hash_equals($secret, $payload['secret']);
		}
		return FALSE;
	}

}
