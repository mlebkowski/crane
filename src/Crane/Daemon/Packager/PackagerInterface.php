<?php


namespace Crane\Daemon\Packager;


interface PackagerInterface
{
	/**
	 * @param string $request
	 * @return bool
	 */
	public function isHandling($request);

	/**
	 * @param string $request
	 * @return string
	 */
	public function getPackage($request);
}