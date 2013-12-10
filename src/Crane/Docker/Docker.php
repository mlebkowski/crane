<?php


namespace Crane\Docker;

use Crane\Configuration\AssetsLocatorInterface;
use Crane\Configuration\Repository;
use Crane\Docker\Image\Image;
use Silex\Application;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\TTY;

class Docker
{
	/** @var string */
	private $postReceivePath;
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
	 * @param callable $executorFactory
	 * @param string   $postReceivePath
	 */
	public function __construct($executorFactory, $postReceivePath)
	{
		$this->executorFactory = $executorFactory;
		$this->postReceivePath = $postReceivePath;
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

	public function copyDockerfiles($path)
	{
		$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg($this->tmpPath)));

		$tarOutput = $this->getLocalExecutor()->cwd($path)->executeCommand('tar -cf - images');
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
	public function getDockerContainer(Image $image)
	{
		return $this->createDockerContainer($image);
	}

	public function startImage(Image $image, AssetsLocatorInterface $assetsLocator)
	{
		$cmd = 'docker run -d -P ';
		if ($image->getUseTTY())
		{
			$cmd .= '-i -t ';
		}
		foreach ($image->getVolumes() as $volume)
		{
			$path = $this->getVolumePathOnTarget($image, $volume);
			$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg($path)));
			$cmd .= sprintf('-v=%s:/home/%s:rw ', escapeshellarg($path), escapeshellarg($volume));

			if ($image->isVolumeGitRoot($volume))
			{
				$this->cloneRepository($path, $image->getRepository());
			}
		}

		$portMapper = $image->getPortMapper();
		foreach ($image->getPorts() as $port)
		{
			$portSpec = sprintf('%s/%d', $image->getName(), $port);
			if ($portMapper->isPortMapped($portSpec))
			{
				$cmd .= sprintf('-p %d:%d ', $portMapper->mapPort($portSpec, $this->getUser()), $port);
			}
		}

		if ($image->getIdentity())
		{
			$privateKey = file_get_contents($assetsLocator->getAssetPath($image, $image->getIdentity()));
			$path = $this->getIdentityPath($image);
			$this->executor->executeCommand(sprintf('mkdir -p %s', escapeshellarg(dirname($path))));
			$this->executor->executeCommand(sprintf('cat > %s', escapeshellarg($path)), $privateKey);
			$this->executor->executeCommand(sprintf('chmod 0600 %s', escapeshellarg($path)), $privateKey);
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


		// add post-receive hook after image has started:
		foreach ($image->getVolumes() as $volume)
		{
			if ($image->isVolumeGitRoot($volume))
			{
				$this->setupPostRevceiveHook($image, $volume);
			}
		}

		return $this->createDockerContainer($image);
	}


	public function remove(DockerContainer $container)
	{
		if ($container->isRunning())
		{
			$this->executor->executeCommand(sprintf('docker stop %s', $container->getName()));
		}
		elseif ($container->isGhost())
		{
			$this->executor->executeCommand(sprintf('docker stop -t=0 %s', $container->getName()));
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

	private function cloneRepository($path, Repository $repository)
	{
		$command = sprintf('git --git-dir=%s/.git config --get remote.origin.url | head -1', escapeshellarg($path));
		$remote = trim($this->executor->executeCommand($command));
		if ($repository->getUrl() !== $remote)
		{
			$path = escapeshellarg($path);
			$url = escapeshellarg($repository->getUrl());
			$this->executor->executeCommand(sprintf('git clone -b %s %s %s', $repository->getBranch(), $url, $path));
		}
	}

	/**
	 * @param Image $image
	 *
	 * @return string
	 */
	private function getIdentityPath(Image $image)
	{
		return vsprintf('%s/%s/identities/%s_rsa', [
			$this->getCranePathForUser(),
			$image->getProjectName(),
			$image->getName()
		]);
	}

	public function runInteractive(Image $image, $command = '')
	{
		if (false === in_array(22, $image->getPorts()))
		{
			throw new \InvalidArgumentException(sprintf('Image %s does not have port 22 open', $image->getName()));
		}

		$command = $this->getSshCommand($command, $image);

		$this->executor->executeCommand($command, new TTY);
	}

	private function getSshCommand($command, Image $image)
	{
		$containter = $this->getDockerContainer($image);
		$port = $containter->getExposedPort(22);
		$host = $containter->getGatewayHost();

		// TODO: this can be done by local executor, not via double ssh!
		// TODO: it would remove AssetsLocatorInterface requirement for building
		return sprintf('ssh -A -t -p %d -i %s %s@%s %s', $port, $this->getIdentityPath($image), $image->getRemoteUser(), $host, escapeshellarg($command));

	}

	private function getPostReceiveScript(Image $image, $volume)
	{
		return strtr(file_get_contents($this->postReceivePath), [
			'##COMMAND##' => $this->getSshCommand('php', $image),
			'##TARGET_VOLUME##' => '/home/' . $volume,
		]);
	}

	private function setupPostRevceiveHook($image, $volume)
	{

		$gitDir = sprintf('%s/.git', $this->getVolumePathOnTarget($image, $volume));
		$path = sprintf('%s/hooks/post-receive', $gitDir);
		$script = $this->getPostReceiveScript($image, $volume);
		$this->executor->executeCommand(sprintf('cat > %s', escapeshellarg($path)), $script);
		$this->executor->executeCommand(sprintf('chmod a+x %s', escapeshellarg($path)));
		$this->executor->executeCommand(sprintf('git --git-dir=%s config --replace-all receive.denyCurrentBranch ignore', $gitDir));
		$this->executor->executeCommand(sprintf('git --git-dir=%s config --replace-all receive.denyDeleteCurrent ignore', $gitDir));
	}

	/**
	 * @param Image  $image
	 * @param string $volume
	 *
	 * @return string
	 */
	private function getVolumePathOnTarget(Image $image, $volume)
	{
		return sprintf('%s/%s/volumes/%s', $this->getCranePathForUser(), $image->getProjectName(), $volume);
	}
}