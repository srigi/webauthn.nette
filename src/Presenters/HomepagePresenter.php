<?php declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class HomepagePresenter extends Nette\Application\UI\Presenter
{
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
