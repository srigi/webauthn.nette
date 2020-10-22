<?php declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI;
use Nette\SmartObject;

final class HomepagePresenter extends UI\Presenter
{
	use SmartObject;

	private string $siteName;

	public function __construct(string $siteName)
	{
		$this->siteName = $siteName;

		parent::__construct();
	}

	public function beforeRender()
	{
		$this->template->siteName = $this->siteName;
	}
}
