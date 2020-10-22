<?php declare(strict_types=1);

namespace App\Lib;

use Nette\Database;
use Nette\SmartObject;

class UserRepository
{
	use SmartObject;

	const
		TABLE_USERS = 'users',
		COLUMN_USERS_ID = 'id';

	private Database\Context $database;

	public function __construct(Database\Context $database)
	{
		$this->database = $database;
	}

	public function findOneById(int $id): ?array
	{
		$user = $this->database->table(self::TABLE_USERS)
			->where(self::COLUMN_USERS_ID, $id)
			->fetch();

		if ($user) {
			return $user->toArray();
		} else {
			return null;
		}
	}
}
