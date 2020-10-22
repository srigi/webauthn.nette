<?php declare(strict_types=1);

namespace App\Lib\Webauthn;

use Nette\Application\UI;
use Nette\Database;
use Nette\Security;
use Nette\SmartObject;

class Authenticator implements Security\IAuthenticator
{
	use SmartObject;

	private const
		TABLE_USERS = 'users',
		COLUMN_USERS_ID = 'id',
		COLUMN_USERS_USERNAME = 'username',
		COLUMN_USERS_PASSWORD = 'password',

		TABLE_HW_CREDENTIALS = 'hw_credentials',
		COLUMN_HW_CREDENTIALS_ID = 'id',
		COLUMN_HW_CREDENTIALS_USERS_ID = 'user_id',
		COLUMN_HW_CREDENTIALS_CREDENTIAL_ID = 'public_key_credential_id';

	private Database\Context $database;

	private Security\Passwords $passwords;

	private UI\Presenter $presenter;

	public function __construct(Database\Context $database, Security\Passwords $passwords)
	{
		$this->database = $database;
		$this->passwords = $passwords;
	}

	public function setPresenter(UI\Presenter $presenter): void
	{
		$this->presenter = $presenter;
	}

	public function authenticate(array $credentials): Security\IIdentity
	{
		[$username, $password] = $credentials;
		$row = $this->database->table(self::TABLE_USERS)
			->where(self::COLUMN_USERS_USERNAME, $username)
			->fetch();

		if (!$row) {
			throw new Security\AuthenticationException('User not found!', self::IDENTITY_NOT_FOUND);
		}

		if (!$this->passwords->verify($password, $row[self::COLUMN_USERS_PASSWORD])) {
			throw new Security\AuthenticationException('Incorrect password!', self::INVALID_CREDENTIAL);
		}

		if ($this->passwords->needsRehash($row[self::COLUMN_USERS_PASSWORD])) {
			$row->update([
				self::COLUMN_USERS_PASSWORD => $this->passwords->hash($password),
			]);
		}

		$userHwCredentials = $this->database->table(self::TABLE_HW_CREDENTIALS)
			->where(self::COLUMN_HW_CREDENTIALS_USERS_ID, $row[self::COLUMN_USERS_ID])
			->fetch();

		if (!$userHwCredentials) {
			return new Security\Identity($row[self::COLUMN_USERS_ID], ['MEMBER'], [
				'username' => $row[self::COLUMN_USERS_USERNAME],
			]);
		} else {
			$this->presenter->forward('Sign:webauthnIn', [
				'id' => $row[self::COLUMN_USERS_ID],
				'username' => $row[self::COLUMN_USERS_USERNAME],
				'hwCredentialId' => $userHwCredentials[self::COLUMN_HW_CREDENTIALS_CREDENTIAL_ID],
			]);
		}
	}
}
