<?php declare(strict_types=1);

namespace App\Lib\Webauthn;

use Assert\Assertion;
use Nette\SmartObject;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\PublicKeyCredentialRequestOptions;

class PublicKeyCredentialRequestOptionsFactory
{
	use SmartObject;

	/** @var array<string, mixed> */
	private array $profiles;

	public function __construct(array $profiles)
	{
		$this->profiles = $profiles;
	}

	public function create(
		string $key,
		array $allowCredentials,
		?string $userVerification = null,
		?AuthenticationExtensionsClientInputs $authenticationExtensionsClientInputs = null
	): PublicKeyCredentialRequestOptions
	{
		Assertion::keyExists($this->profiles, $key, sprintf('The profile with key "%s" does not exist.', $key));
		$profile = $this->profiles[$key];

		$options = new PublicKeyCredentialRequestOptions(
			\random_bytes($profile['challenge_length']),
			$profile['timeout'],
			$profile['rp_id'],
			$allowCredentials,
			$userVerification ?? $profile['user_verification'],
			$authenticationExtensionsClientInputs ?? $this->createExtensions($profile)
		);

		return $options;
	}

	private function createExtensions(array $profile): AuthenticationExtensionsClientInputs
	{
		$extensions = new AuthenticationExtensionsClientInputs();
		foreach ($profile['extensions'] as$k => $v) {
			$extensions->add(new AuthenticationExtension($k, $v));
		}

		return $extensions;
	}
}
