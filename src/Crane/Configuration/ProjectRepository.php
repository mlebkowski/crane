<?php


namespace Crane\Configuration;


use Crane\Docker\Executor\CommandExecutor;
use Crane\Docker\Image\Image;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProjectRepository implements AssetsLocatorInterface
{
	/** @var string */
	private $configPath;
	/**
	 * @var CommandExecutor
	 */
	private $executor;

	public function __construct(CommandExecutor $executor, $configPath)
	{
		$this->configPath = $configPath;
		$this->executor = $executor;
	}

	public function getNameFromRepository($url, $branch = null)
	{
		$branch = $branch ? : "master";
		$url = escapeshellarg($url);
		try
		{
			$cmd = sprintf('git archive --remote=%s %s: crane.json', $url, $branch);
			$archive = $this->executor->executeCommand($cmd);
			$configJson = $this->executor->executeCommand('tar -xO -', $archive);
		}
		catch (ProcessFailedException $e)
		{
			$error = "Invalid command: 'git-upload-archive";
			$stdout = $e->getProcess()->getErrorOutput();

			if (substr($stdout, 0, strlen($error)) !== $error)
			{
				throw $e;
			}
			$tempnam = sys_get_temp_dir() . '/crane_clone_' . uniqid(substr(md5(time()), 0, 6));
			$cmd = sprintf('git clone -b %s -n --depth=1 %s %s', $branch, $url, $tempnam);
			$this->executor->executeCommand($cmd);
			$cmd = sprintf('git show %s:crane.json', $branch);
			$configJson = $this->executor->cwd($tempnam)->executeCommand($cmd);
		}
		return json_decode($configJson, true)['name'];
	}

	public function hasProject($name)
	{
		return is_dir($this->getProjectDirectory($name));
	}

	public function isProjectFromRepository($name, $url)
	{
		$path = $this->getProjectDirectory($name);
		$cmd = 'git config --get remote.origin.url';
		return $url === trim($this->executor->cwd($path)->executeCommand($cmd));
	}

	public function updateProject($name)
	{
		$path = $this->getProjectDirectory($name);
		$this->executor->cwd($path)->executeCommand('git pull --rebase');
	}

	public function saveProject($url, $branch = null)
	{
		$name = $this->getNameFromRepository($url, $branch);
		$path = escapeshellarg($this->getProjectDirectory($name));
		$url = escapeshellarg($url);
		$this->executor->executeCommand(sprintf('git clone -b %s %s %s', $branch?:"master", $url, $path));
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function getProjectDirectory($name)
	{
		return sprintf('%s/%s', $this->configPath, $name);
	}

	public function getConfig($name)
	{
		if ($this->hasProject($name))
		{
			return json_decode(file_get_contents($this->getProjectDirectory($name) . '/crane.json'), true);
		}
		throw new \InvalidArgumentException('Cannot find project by that name: ' . $name);
	}

	/**
	 * @param Image  $image
	 * @param string $name
	 *
	 * @return string
	 */
	public function getAssetPath(Image $image, $name)
	{
		return vsprintf('%s/images/%s/%s', [
			$this->getProjectDirectory($image->getProjectName(true)),
			$image->getName(),
			$name
		]);
	}
}