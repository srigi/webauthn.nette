<?php declare(strict_types=1);

namespace App;

use Nette\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$appDebug = (bool) getenv('APP_DEBUG');
		putenv(sprintf('LOGGING_LEVEL=%s', $appDebug === true ? 'DEBUG' : 'ERROR'));

		// Azure cannot save sessions elsewhere than in /tmp in app-service 🤦‍
		// setup SESSION_SAVE_PATH by APP_ENV
		$appEnv = getenv('APP_ENV');
		$tempDir = __DIR__ . '/../temp';
		putenv(sprintf('SESSION_SAVE_PATH=%s', $appEnv === 'local' ? "${tempDir}/sessions" : '/tmp'));

		$configurator = new Configurator;
		$configurator->setDebugMode($appDebug);
		$configurator->enableTracy(null);
		$configurator->setTimeZone(getenv('TIMEZONE'));
		$configurator->setTempDirectory($tempDir);
		$configurator->addConfig(__DIR__ . '/../config/common.neon');

		return $configurator;
	}
}
