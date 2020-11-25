<?php declare(strict_types=1);

namespace App\Presenters;

use Nette\Application;
use Nette\Http;
use Nette\SmartObject;
use Tracy\ILogger;

final class ErrorPresenter implements Application\IPresenter
{
	use SmartObject;

	private ILOgger $logger;

	public function __construct(ILogger $logger)
	{
		$this->logger = $logger;
	}

	public function run(Application\Request $request): Application\IResponse
	{
		$exception = $request->getParameter('exception');

		if ($exception instanceof Application\BadRequestException) {
			[$module, , $sep] = Application\Helpers::splitName($request->getPresenterName());

			return new Application\Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
		}

		$this->logger->log($exception, ILogger::EXCEPTION);
		return new Application\Responses\CallbackResponse(function(Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
				require __DIR__ . '/../templates/Error/500.phtml';
			}
		});
	}
}
