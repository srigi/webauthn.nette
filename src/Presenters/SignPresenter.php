<?php declare(strict_types=1);

namespace App\Presenters;

use App\Forms;
use Nette\Application\BadRequestException;
use Nette\Application\UI;
use Nette\SmartObject;

class SignPresenter extends BasePresenter
{
	use SmartObject;

	/** @persistent */
	public string $backlink = '';

	private Forms\SignInFormFactory $signInFactory;

	public function __construct(Forms\SignInFormFactory $signInFactory)
	{
		$this->signInFactory = $signInFactory;
	}

	public function actionWebauthnIn(): void
	{
		$params = $this->getParameters();
		if (!isset($params['username'])) {
			throw new BadRequestException();
		}

		$this->template->username = $params['username'];
	}

	public function actionOut(): void
	{
		$this->getUser()->logout();
	}

	protected function createComponentSignInForm(): UI\Form
	{
		return $this->signInFactory->create($this, function(): void {
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		});
	}
}
