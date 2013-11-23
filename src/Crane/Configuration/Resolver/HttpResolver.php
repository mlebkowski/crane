<?php

namespace Crane\Configuration\Resolver;

use Crane\Configuration\CranePhar;
use Crane\Configuration\CraneScheme;

class HttpResolver extends FilesystemResolver
{
	public function isHandling($uri)
	{
		return preg_match('#^https?://#', $uri);
	}

	public function resolve($uri)
	{
		$uri = CraneScheme::getUri($uri);
		$tempnam = rtrim(sys_get_temp_dir(), '/') . '/crane_' . md5($uri) . '.tar';
		file_put_contents($tempnam, file_get_contents($uri));
		try
		{
			$phar = new CranePhar($tempnam);
		}
		catch (\UnexpectedValueException $e)
		{
			unlink($tempnam);
			$str = sprintf('Invalid data received from %s', $uri);
			throw new \RuntimeException($str, 0, $e);
		}
		return $phar;
	}

}