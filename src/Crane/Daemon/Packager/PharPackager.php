<?php

namespace Crane\Daemon\Packager;

class PharPackager implements PackagerInterface
{

	/**
	 * @param string $request
	 *
	 * @return bool
	 */
	public function isHandling($request)
	{
		$request = $this->getFilename($request);
		if (null === $request)
		{
			return false;
		}

		try
		{
			new \Phar($request);
			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * @param string $request
	 * @return \Phar
	 */
	public function getPackage($request)
	{
		return $this->getFilename($request);
	}

	/**
	 * @param $request
	 *
	 * @return mixed
	 */
	protected function getFilename($request)
	{
		foreach (["", "." . $this->getExtension(), "/crane." . $this->getExtension()] as $suffix)
		{
			if (file_exists($request . $suffix))
			{
				return $request . $suffix;
			}
		}
		return null;
	}

	private function getExtension()
	{
		return 'tar';
	}
}