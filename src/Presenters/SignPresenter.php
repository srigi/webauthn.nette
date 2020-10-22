<?php declare(strict_types=1);

namespace App\Presenters;

use App\Forms;
use Nette\Application\UI;
use Nette\SmartObject;

class SignPresenter extends UI\Presenter
{
	use SmartObject;

	/** @persistent */
	public string $backlink = '';

	private Forms\SignInFormFactory $signInFactory;

	private string $siteName;

	public function __construct(Forms\SignInFormFactory $signInFactory, string $siteName)
	{
		$this->signInFactory = $signInFactory;
		$this->siteName = $siteName;
	}

	public function beforeRender()
	{
		$this->template->siteName = $this->siteName;
	}

	public function actionOut(): void
	{
		$this->getUser()->logout();
	}

	protected function createComponentSignInForm(): UI\Form
	{
		return $this->signInFactory->create(function(): void {
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		});
	}
}
