<?php declare(strict_types=1);

namespace App\Presenters;

use App\Lib;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Nette\Http\Response;
use Nette\SmartObject;
use Nette\Utils\Json;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Webauthn;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialSource;

class WebauthnPresenter extends BasePresenter
{
	use SmartObject;

	const SESSION_SECTION_ATTESTATION = 'attestation';

	/** @inject */
	public Lib\Webauthn\CredentialSourceRepository $credentialSourceRepository;

	/** @inject */
	public Lib\Webauthn\PublicKeyCredentialCreationOptionsFactory $credentialCreationOptionsFactory;

	/** @inject */
	public Psr17Factory $psr17Factory;

	/** @inject */
	public Webauthn\PublicKeyCredentialLoader $publicKeyCredentialLoader;

	/** @inject */
	public Webauthn\AuthenticatorAttestationResponseValidator $attestationResponseValidator;

	public function startup(): void
	{
		parent::startup();

		if (!$this->getUser()->isLoggedIn()) {
			throw new BadRequestException('Unauthorized!', IResponse::S401_UNAUTHORIZED);
		}
	}

	public function renderAddHwCredential(): void
	{
		$user = $this->getUser();
		$identity = $user->getIdentity();
		$publicKeyUserEntity = new Webauthn\PublicKeyCredentialUserEntity(
			$identity->getData()['username'],
			(string) $user->getId(),
			$identity->getData()['username'],
		);
		$publicKeyCredentialCreationOptions = $this->credentialCreationOptionsFactory->create('default', $publicKeyUserEntity);

		$this->getSession(self::SESSION_SECTION_ATTESTATION)->publicKeyUserEntity = $publicKeyUserEntity;
		$this->getSession(self::SESSION_SECTION_ATTESTATION)->publicKeyCredentialCreationOptions = $publicKeyCredentialCreationOptions;
		$this->template->credentialCreationOptions = Json::encode(
			$publicKeyCredentialCreationOptions,
			JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES
		);
	}

	public function actionVerifyAttestation(): void
	{
		if ($this->getHttpRequest()->getHeader('content-type') !== 'application/json') {
			throw new BadRequestException('You must POST JSON body!', IResponse::S406_NOT_ACCEPTABLE);
		}

		$session = $this->getSession(self::SESSION_SECTION_ATTESTATION);
		$publicKeyUserEntity = $session->publicKeyUserEntity;
		$publicKeyCredentialCreationOptions = $session->publicKeyCredentialCreationOptions;
		if (!$publicKeyUserEntity || !$publicKeyCredentialCreationOptions) {
			$this->redirect('addHwCredential');
		}

		$attestation = file_get_contents('php://input');
		$publicKeyCredential = $this->publicKeyCredentialLoader->load($attestation);
		$response = $publicKeyCredential->getResponse();
		if (!$response instanceof AuthenticatorAttestationResponse) {
			throw new BadRequestException('Invalid attestation');
		}

		$urlObj = $this->getHttpRequest()->getUrl();
		$url = "{$urlObj->getScheme()}://{$urlObj->getHost()}:{$urlObj->getPort()}{$urlObj->getPath()}";
		$psr7Request = $this->psr17Factory
			->createServerRequest($this->getHttpRequest()->getMethod(), $url)
			->withParsedBody(JSON::decode($attestation));

		try {
			$publicKeyCredentialSource = $this->attestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);
		} catch (\Throwable $throwable) {
			throw new BadRequestException('Invalid assertion', Response::S422_UNPROCESSABLE_ENTITY, $throwable);
		}

		$this->credentialSourceRepository->saveCredentialSource(new PublicKeyCredentialSource(
			$publicKeyCredentialSource->getPublicKeyCredentialId(),
			$publicKeyCredentialSource->getType(),
			$publicKeyCredentialSource->getTransports(),
			$publicKeyCredentialSource->getAttestationType(),
			$publicKeyCredentialSource->getTrustPath(),
			$publicKeyCredentialSource->getAaguid(),
			$publicKeyCredentialSource->getCredentialPublicKey(),
			$publicKeyCredentialSource->getUserHandle(),
			$publicKeyCredentialSource->getCounter(),
		));
		$this->sendJson([
			'status' => 'OK',
		]);
	}
}
