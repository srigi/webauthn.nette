<?php declare(strict_types=1);

namespace App\Lib\Webauthn;

use Nette\Database;
use Nette\SmartObject;
use Nette\Utils\Json;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
	use SmartObject;

	const
		TABLE_HW_CREDENTIALS = 'hw_credentials',
		COLUMN_HW_CREDENTIALS_PUBLIC_KEY_CREDENTIAL_ID = 'public_key_credential_id';

	private Database\Context $database;

	public function __construct(Database\Context $database)
	{
		$this->database = $database;
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
	{
		$hwCredentials = $this->database->table(self::TABLE_HW_CREDENTIALS)
			->where(self::COLUMN_HW_CREDENTIALS_PUBLIC_KEY_CREDENTIAL_ID, \base64_encode($publicKeyCredentialId))
			->fetch();

		if ($hwCredentials) {

		} else {
			return null;
		}
	}

	public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
	{
	}

	public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
	{
		$data = [
			'user_id' => $publicKeyCredentialSource->getUserHandle(),
			'public_key_credential_id' => \base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()),
			'type' => $publicKeyCredentialSource->getType(),
			'transports' => Json::encode($publicKeyCredentialSource->getTransports()),
			'attestation_type' => $publicKeyCredentialSource->getAttestationType(),
			'trust_path' => Json::encode($publicKeyCredentialSource->getTrustPath()->jsonSerialize()),
			'aaguid' => $publicKeyCredentialSource->getAaguid()->toString(),
			'credential_public_key' => \base64_encode($publicKeyCredentialSource->getCredentialPublicKey()),
			'user_handle' => $publicKeyCredentialSource->getUserHandle(),
			'counter' => $publicKeyCredentialSource->getCounter(),
		];

		$this->database->table(self::TABLE_HW_CREDENTIALS)
			->insert($data);
	}
}
