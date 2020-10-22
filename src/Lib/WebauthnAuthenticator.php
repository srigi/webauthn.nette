<?php declare(strict_types=1);

namespace App\Lib;

use Nette\Application\UI;
use Nette\Database;
use Nette\Security;
use Nette\SmartObject;

class WebauthnAuthenticator implements Security\IAuthenticator
{
	use SmartObject;

	private const
		TABLE_NAME = 'users',
		COLUMN_ID = 'id',
		COLUMN_USERNAME = 'username',
		COLUMN_PASSWORD = 'password';

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
		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_USERNAME, $username)
			->fetch();

		if (!$row) {
			throw new Security\AuthenticationException('User not found!', self::IDENTITY_NOT_FOUND);
		}

		if (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD])) {
			throw new Security\AuthenticationException('Incorrect password!', self::INVALID_CREDENTIAL);
		}

		if ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD])) {
			$row->update([
				self::COLUMN_PASSWORD => $this->passwords->hash($password),
			]);
		}

		$this->presenter->forward('Sign:webauthnIn', [
			'id' => $row[self::COLUMN_ID],
			'username' => $row[self::COLUMN_USERNAME],
		]);
	}
}
