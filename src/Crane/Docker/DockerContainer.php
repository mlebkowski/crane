<?php


namespace Crane\Docker;


use Crane\Docker\Executor\CommandExecutor;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerContainer
{

	private $name;
	/**
	 * @var Executor\CommandExecutor
	 */
	private $executor;
	private $inspectResults;

	public function __construct($name, CommandExecutor $executor)
	{
		$this->name = $name;
		$this->executor = $executor;
	}

	public function getFirstExposedPort()
	{
		return array_values($this->getInspectResults()["NetworkSettings"]['Ports'])[0][0]['HostPort'];
	}

	public function exists()
	{
		return null !== $this->getInspectResults();
	}

	public function isRunning()
	{
		$inspectResults = $this->getInspectResults();
		return $inspectResults['State']['Running'] && false === $inspectResults['State']['Ghost'];
	}

	public function getGatewayHost()
	{
		return $this->getInspectResults()["NetworkSettings"]['Gateway'];
	}

	public function getInspectResults($skipCache = false)
	{
		if (null === $this->inspectResults || true === $skipCache)
		{
			try
			{
				$data = $this->executor->executeCommand(sprintf('docker inspect %s', $this->name), null, true);
				$data = json_decode($data, true);
				$this->inspectResults = $data[0];
			}
			catch (ProcessFailedException $e){}
		}
		return $this->inspectResults;
	}

	public function getId($abbrev = true)
	{
		$id = $this->getInspectResults()['ID'];
		return $abbrev ? substr($id, 0, 9) : $id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function reset()
	{
		$this->inspectResults = null;
	}
}