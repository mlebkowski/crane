<?php


namespace Crane\Daemon;


use Crane\Daemon\Packager\PackagerFactory;
use Symfony\Component\Process\Process;

class Daemon
{
	const CRANE_PORT = '27263';
	/** @var string */
	private $directory;
	/**
	 * @var PackagerFactory
	 */
	private $factory;

	public function __construct($directory, PackagerFactory $factory)
	{
		$this->setDirectory($directory);
		$this->factory = $factory;
	}

	/**
	 * @param string $directory
	 */
	public function setDirectory($directory)
	{
		$this->directory = rtrim($directory, '/');
	}

	public function getPackage($request)
	{
		$request = $this->directory . '/' . $request;
		try
		{
			return $this->factory->getPackage($request);
		}
		catch (\RuntimeException $e)
		{
			return null;
		}
	}

	public function getJson($request)
	{
		$request = $this->directory . '/' . $request . '/crane.json';
		if (file_exists($request))
		{
			return $request;
		}
		return null;
	}

	public function getContents($request, $type)
	{
		switch ($type)
		{
			case 'application/json': return $this->getJson($request);
			case 'application/x-tar': return $this->getPackage($request);
		}
		return null;
	}

}