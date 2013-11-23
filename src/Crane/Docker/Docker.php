<?php


namespace Crane\Docker;

use Crane\Docker\Image\Image;
use Silex\Application;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Docker
{
	/** @var callable */
	private $executorFactory;
	/** @var DockerContainer[] */
	private $containers;
	/** @var System\User */
	private $user;
	/**
	 * @var Executor\CommandExecutor
	 */
	private $executor;

	private $tmpPath;

	/**
	 * @param callable   $executorFactory
	 */
	public function __construct($executorFactory)
	{
		$this->executorFactory = $executorFactory;
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
		throw new \RuntimeException('Not implemented');
//		$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg($this->tmpPath)));
//
//		$path = $this->imagesPath;
//		$dir = basename($path);
//		$path = dirname($path);
//
//		$command = sprintf('tar -cf - -C %s %s', escapeshellarg($path), escapeshellarg($dir));
//		$tarOutput = $this->getLocalExecutor()->executeCommand($command);
//		$this->executor->executeCommand(sprintf('tar -xf - -C %s', escapeshellarg($this->tmpPath)), $tarOutput);
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
	public function getDockerContainer(Image $image)
	{
		return $this->createDockerContainer($image);
	}

	public function startImage(Image $image)
	{
		$cmd = 'docker run -d -P ';
		if ($image->getUseTTY())
		{
			$cmd .= '-i -t ';
		}
		foreach ($image->getVolumes() as $volume)
		{
			$path = sprintf('%s/%s/volumes/%s', $this->getCranePathForUser(), $image->getProjectName(), $volume);
			$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg($path)));
			$cmd .= sprintf('-v=%s:/home/%s:rw ', escapeshellarg($path), escapeshellarg($volume));

			if ($image->isVolumeGitRoot($volume))
			{
				$this->cloneRepository($path, $image->getRepository()->getUrl());
			}
		}

		$portMapper = $image->getPortMapper();
		foreach ($image->getPorts() as $port)
		{
			if ($portMapper->isPortMapped($port))
			{
				$cmd .= sprintf('-p %d:%d ', $portMapper->mapPort($port, $this->getUser()), $port);
			}
		}

		$kernelHost = null;
		foreach ($image->getRequiredImages()->getArrayCopy() as $dep)
		{
			if (false === $dep->isRunnable())
			{
				continue;
			}

			$container = $this->getDockerContainer($dep);
			if (null === $kernelHost)
			{
				$kernelHost = $container->getGatewayHost();
				$cmd .= sprintf('-e KERNEL_HOST=%s ', $kernelHost);
			}

			$port = $container->getFirstExposedPort();
			$envName = sprintf('%s_PORT', strtoupper($dep->getName()));
			$cmd .= sprintf('-e %s=%s ', $envName, $port);

		}

		$cmd .= sprintf('-name=%s ', $image->getRunningName($this->getUser()->getName()));

		$cmd .= $image->getFullName();


		$this->executor->executeCommand($cmd);
		return $this->createDockerContainer($image);
	}


	public function remove(DockerContainer $container)
	{
		if ($container->isRunning())
		{
			$this->executor->executeCommand(sprintf('docker stop %s', $container->getName()));
		}
		$this->executor->executeCommand(sprintf('docker rm %s', $container->getName()));
		$container->reset();
	}

	/**
	 * @return Executor\CommandExecutor
	 */
	private function getLocalExecutor()
	{
		$factory = $this->executorFactory;
		return $factory();
	}

	private function getCranePathForUser()
	{
		return $this->getUser()->getHome() . '/.crane';
	}

	private function getUser()
	{
		if (null === $this->user)
		{
			$command = 'echo -n; id -u $USER; echo $USER; grep $USER /etc/passwd | cut -d: -f6';
			$rsp = $this->executor->executeCommand($command);
			list ($id, $name, $home) = explode("\n", $rsp);
			$this->user = new System\User($id, $name, $home);
		}
		return $this->user;
	}

	/**
	 * @param Image $image
	 *
	 * @return DockerContainer
	 */
	private function createDockerContainer(Image $image)
	{
		$name = $image->getRunningName($this->getUser()->getName());
		if (false === isset($this->containers[$name]))
		{
			$dockerContainer = new DockerContainer($name, $this->executor);
			$this->containers[$name] = $dockerContainer;
		}
		return $this->containers[$name];
	}

	private function cloneRepository($path, $url)
	{
		$path = escapeshellarg($path);
		$url = escapeshellarg($url);
		$command = sprintf('git remote -v --git-dir=%s | grep %s | head -1', $path, $url);
		$remote = trim($this->executor->executeCommand($command));
		if ("" === $remote)
		{
			$this->executor->executeCommand(sprintf('git clone %s %s', $url, $path));
		}
	}

}