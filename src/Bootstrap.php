<?php declare(strict_types=1);

namespace App;

use Nette\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$appDebug = (bool) getenv('APP_DEBUG');
		$loggingLevel = $appDebug === true ? 'DEBUG' : 'ERROR';
		putenv("LOGGING_LEVEL=$loggingLevel");

		$configurator = new Configurator;
		$configurator->setDebugMode($appDebug);
		$configurator->enableTracy(null);
		$configurator->setTimeZone(getenv('TIMEZONE'));
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->addConfig(__DIR__ . '/../config/common.neon');

		return $configurator;
	}
}
