<?php declare(strict_types=1);

namespace App;

use Nette\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$configurator->setDebugMode(getenv('APP_DEBUG') == true);
		$configurator->enableTracy(__DIR__ . '/../logs');
		$configurator->setTimeZone(getenv('TIMEZONE'));
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->addConfig(__DIR__ . '/../config/common.neon');

		if (file_exists($file = __DIR__ . '/../config/local.neon')) {
			$configurator->addConfig($file);
		}

		return $configurator;
	}
}
