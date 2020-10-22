<?php declare(strict_types=1);

namespace App\Components;

use App\Lib;
use Nette\Application\UI;
use Nette\SmartObject;

class Navbar extends UI\Control
{
	use SmartObject;

	private Lib\Sitename $siteName;

	public function __construct(Lib\SiteName $siteName)
	{
		$this->siteName = $siteName;
	}

	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/../templates/components/navbar.latte');
		$this->template->siteName = $this->siteName->getSiteName();
		$this->template->render();
	}
}
