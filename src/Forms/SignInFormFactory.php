<?php declare(strict_types=1);

namespace App\Forms;

use App\Lib\Webauthn;
use Nette\Application\UI;
use Nette\Security;
use Nette\SmartObject;

final class SignInFormFactory
{
	use SmartObject;

	private Webauthn\FirstFactorAuthenticator $authenticator;

	private Security\User $user;

	public function __construct(Webauthn\FirstFactorAuthenticator $authenticator, Security\User $user)
	{
		$this->authenticator = $authenticator;
		$this->user = $user;
	}

	public function create(UI\Presenter $presenter, $onSuccess): UI\Form
	{
		$form = new UI\Form();
		$form->addText('username', 'Username:')
			->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = function(UI\Form $form, \stdClass $values) use ($presenter, $onSuccess): void {
			$this->authenticator->setPresenter($presenter);

			try {
				$this->user->login($values->username, $values->password);
			} catch (Security\AuthenticationException $e) {
				$form->addError('The provided credentials are incorrect!');

				return;
			}

			$onSuccess();
		};

		return $form;
	}
}
