<?php declare(strict_types=1);

namespace App\Presenters;

use Nette\Application;
use Nette\Application\UI;
use Nette\SmartObject;

final class Error4xxPresenter extends UI\Presenter
{
	use SmartObject;

	public function startup(): void
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(Application\Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(Application\BadRequestException $exception): void
	{
		// load template 403.latte or 404.latte or ... 4xx.latte
		$file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
		$this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
	}
}
