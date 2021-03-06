<?php declare(strict_types=1);

namespace App\Presenters;

use App\Lib;
use App\Components;
use Nette\Application\UI;
use Nette\SmartObject;

abstract class BasePresenter extends UI\Presenter
{
	use SmartObject;

	/** @inject  */
	public Lib\SiteName $siteName;

	public function beforeRender()
	{
		$this->template->siteName = $this->siteName->getSiteName();
	}

	public function createComponentNavbar(): UI\Control
	{
		return new Components\Navbar($this->siteName);
	}
}
