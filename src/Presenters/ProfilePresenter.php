<?php declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Database;
use Nette\Http\IResponse;
use Nette\SmartObject;

final class ProfilePresenter extends BasePresenter
{
	use SmartObject;

	private const TABLE_HW_CREDENTIALS = 'hw_credentials';

	/** @inject */
	public Database\Context $database;

	public function startup(): void
	{
		parent::startup();

		if (!$this->getUser()->isLoggedIn()) {
			throw new BadRequestException('Unauthorized!', IResponse::S401_UNAUTHORIZED);
		}
	}

	public function renderDefault(): void
	{
		$myHwCredentials = $this->database->table(self::TABLE_HW_CREDENTIALS)
			->where('user_id', $this->getUser()->getId())
			->fetchAll();

		$this->template->myHwCredentials = $myHwCredentials;
	}


}
