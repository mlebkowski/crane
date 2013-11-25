<?php


namespace Crane\Configuration;


use Crane\Docker\Executor\CommandExecutor;

class ProjectRepository
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

	public function getNameFromRepository($url)
	{
		$cmd = sprintf('git archive --remote=%s HEAD: crane.json | tar -xO', escapeshellarg($url));
		$configJson = $this->executor->executeCommand($cmd);
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

	public function saveProject($url)
	{
		$name = $this->getNameFromRepository($url);
		$path = escapeshellarg($this->getProjectDirectory($name));
		$url = escapeshellarg($url);
		$this->executor->executeCommand(sprintf('git clone %s %s', $url, $path));
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
}