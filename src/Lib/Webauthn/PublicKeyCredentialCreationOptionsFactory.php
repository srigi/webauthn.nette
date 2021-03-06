<?php declare(strict_types=1);

namespace App\Lib\Webauthn;

use Assert\Assertion;
use Nette\SmartObject;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialCreationOptionsFactory
{
	use SmartObject;

	/** @var array<string, mixed> */
	private array $profiles;

	public function __construct(array $profiles)
	{
		$this->profiles = $profiles;
	}

	public function create(
		string $key, PublicKeyCredentialUserEntity $userEntity,
		array $excludeCredentials = [],
		?AuthenticatorSelectionCriteria $authenticatorSelection = null,
		?string $attestationConveyance = null,
		?AuthenticationExtensionsClientInputs $authenticationExtensionsClientInputs = null
	): PublicKeyCredentialCreationOptions
	{
		Assertion::keyExists($this->profiles, $key, sprintf('The profile with key "%s" does not exist.', $key));
		$profile = $this->profiles[$key];

		$options = new PublicKeyCredentialCreationOptions(
			$this->createRpEntity($profile),
			$userEntity,
			\random_bytes($profile['challenge_length']),
			$this->createCredentialParameters($profile),
			$profile['timeout'],
			$excludeCredentials,
			$authenticatorSelection ?? $this->createAuthenticatorSelectionCriteria($profile),
			$attestationConveyance ?? $profile['attestation_conveyance'],
			$authenticationExtensionsClientInputs ?? $this->createExtensions($profile)
		);

		return $options;
	}

	private function createRpEntity(array $profile): PublicKeyCredentialRpEntity
	{
		return new PublicKeyCredentialRpEntity($profile['rp']['name'], $profile['rp']['id'], $profile['rp']['icon']);
	}

	private function createCredentialParameters(array $profile): array
	{
		$callback = static function ($alg): PublicKeyCredentialParameters {
			return new PublicKeyCredentialParameters(
				PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
				$alg
			);
		};

		return array_map($callback, $profile['public_key_credential_parameters']);
	}

	private function createAuthenticatorSelectionCriteria(array $profile): AuthenticatorSelectionCriteria
	{
		return new AuthenticatorSelectionCriteria(
			$profile['authenticator_selection_criteria']['attachment_mode'],
			$profile['authenticator_selection_criteria']['require_resident_key'],
			$profile['authenticator_selection_criteria']['user_verification']
		);
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
