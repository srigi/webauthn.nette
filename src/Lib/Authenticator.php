<?php declare(strict_types=1);

namespace App\Lib;

use Nette\Security;
use Nette\SmartObject;

class Authenticator implements Security\IAuthenticator
{
	use SmartObject;

	// TODO implement real users database
	static private array $registeredUsers = [
		'alice@example.com' => '$2y$10$zoVe6qqXL8TvkqzuDjLyM.TmBNilL0aVLRxjchjzR1SWfgUGQrqsa', // password: secret
		'bob@example.com' => '$2y$10$xNtPDEcCk7LIqWuxRjT7hOM4y0HcnArCUrSMX0gz1AJ9kfHLyaPL.', // password: secret
	];

	private Security\Passwords $passwords;

	public function __construct(Security\Passwords $passwords)
	{
		$this->passwords = $passwords;
	}

	public function authenticate(array $credentials): Security\IIdentity
	{
		[$username, $password] = $credentials;

		if (!in_array($username, array_keys(self::$registeredUsers))) {
			throw new Security\AuthenticationException('User not found!');
		}

		if (!$this->passwords->verify($password, self::$registeredUsers[$username])) {
			throw new Security\AuthenticationException('Invalid password.');
		}

		return new Security\Identity($username, ['MEMBER'], ['username' => $username]);
	}
}
