<?php


namespace Crane\Docker;

use Crane\Docker\Image\Image;
use Silex\Application;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Docker
{
	/**
	 * @var Executor\CommandExecutor
	 */
	private $executor;

	/**
	 * @var
	 */
	private $app;

	private $tmpPath;

	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->tmpPath = '/tmp/docker_' . substr(sha1(uniqid()), 0, 8);
	}

	public function isDockerAvailable()
	{
		try
		{
			$this->executor->executeCommand('docker info');
			return true;
		}
		catch (ProcessFailedException $e)
		{
			return false;
		}
	}

	public function copyDockerfiles()
	{
		$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg($this->tmpPath)));

		$path = $this->app['path.images'];
		$dir = basename($path);
		$path = dirname($path);

		$command = sprintf('tar -cf - -C %s %s', escapeshellarg($path), escapeshellarg($dir));
		$tarOutput = $this->getLocalExecutor()->executeCommand($command);
		$this->executor->executeCommand(sprintf('tar -xf - -C %s', escapeshellarg($this->tmpPath)), $tarOutput);
	}

	/**
	 * @param \Crane\Docker\Executor\CommandExecutor $executor
	 */
	public function setExecutor(Executor\CommandExecutor $executor)
	{
		$this->executor = $executor;
	}

	public function buildImage(Image $image)
	{
		$command = sprintf('docker build -t %s %s/images/%s', $image->getFullName(), $this->tmpPath, $image->getName());
		$this->executor->executeCommand($command);
	}

	public function getLastError()
	{
		return $this->executor->getLastErrorOutput();
	}
	/**
	 * @param Image $image
	 * @return bool
	 */
	public function isImageBuilt(Image $image)
	{
		try
		{
			$command = sprintf('docker images | grep -cE "^%s\s"', $image->getFullName());
			$this->executor->executeCommand($command);
			return true;
		}
		catch (ProcessFailedException $e)
		{
			return false;
		}

	}

	/**
	 * @return Executor\CommandExecutor
	 */
	private function getLocalExecutor()
	{
		return $this->app['executor.command'];
	}

}