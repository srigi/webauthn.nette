<?php declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI;
use Nette\Security;
use Nette\SmartObject;

final class SignInFormFactory
{
	use SmartObject;

	private Security\User $user;

	public function __construct(Security\User $user)
	{
		$this->user = $user;
	}

	public function create(callable $onSuccess): UI\Form
	{
		$form = new UI\Form();
		$form->addText('username', 'Username:')
			->setRequired('Please enter your username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = function(UI\Form $form, \stdClass $values) use ($onSuccess): void {
			try {
				$this->user->setExpiration($values->remember ? '14 days' : null);
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
