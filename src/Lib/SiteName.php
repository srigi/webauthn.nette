<?php declare(strict_types=1);

namespace App\Lib;

use Nette\SmartObject;

class SiteName
{
	use SmartObject;

	private string $siteName;

	public function __construct(string $siteName)
	{
		$this->siteName = $siteName;
	}

	public function getSiteName(): string
	{
		return $this->siteName;
	}

}
