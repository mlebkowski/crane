<?php

namespace Crane\Daemon\Packager;

class DirectoryPackager implements PackagerInterface
{

	/**
	 * @param string $request
	 *
	 * @return bool
	 */
	public function isHandling($request)
	{
		return is_dir($request) && file_exists(rtrim($request, "/") . "/crane.json");
	}

	/**
	 * @param string $request
	 * @return string
	 */
	public function getPackage($request)
	{
		$path = $request . '/crane.tar';
		$phar = new \PharData($path);
		$phar->buildFromDirectory($request, '/(?<!\.tar)$/i');
		return $path;
	}
}