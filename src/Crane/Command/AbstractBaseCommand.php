<?php


namespace Crane\Command;


use Crane\Configuration\GlobalConfiguration;
use Crane\Configuration\Project;
use Crane\Docker\Docker;
use Crane\Docker\Executor\ExecutorFactory;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractBaseCommand extends Command
{
	const ARGUMENT_NAME = 'name';
	const ARGUMENT_TARGET = 'target';

	/**
	 * @var Project
	 */
	private $project;

	/**
	 * @param InputInterface $input
	 * @return \Crane\Docker\Image\Image
	 * @throws \RuntimeException
	 */
	protected function getImage(InputInterface $input)
	{
		$name = $input->getArgument(self::ARGUMENT_NAME);
		$this->project = $this->getProjectByName($name);
		return $this->project->getMainImage();
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @throws \InvalidArgumentException
	 * @return Docker
	 */
	protected function getDocker(InputInterface $input, OutputInterface $output)
	{
		if ($this->getDefinition()->hasArgument(self::ARGUMENT_TARGET))
		{
			$target = $input->getArgument(self::ARGUMENT_TARGET) ?: $this->project->getCurrentTarget();
		}
		else
		{
			$target = $this->project->getCurrentTarget();
		}
		$targets = $this->project->getTargets();
		if (false === $targets->offsetExists($target))
		{
			throw new \InvalidArgumentException(
				'You must choose a valid target to start the containers. Valid options are:' . "\n - "
				. implode("\n - ", array_keys($targets->getArrayCopy()))
			);
		}
		$ssh = $targets->offsetGet($target);

		/** @var ExecutorFactory $factory */
		$factory = $this->getApplication()->getService('executor.factory');
		$executor = $factory->createExecutor($output, true, $ssh);

		/** @var Docker $docker */
		$docker = $this->getApplication()->getService('docker');
		$docker->setExecutor($executor);

		return $docker;
	}

	private function getProjectByName($name)
	{
		/** @var GlobalConfiguration $globalConfiguration */
		$globalConfiguration = $this->getApplication()->getService('configuration');
		if ($globalConfiguration->offsetExists($name))
		{
			return $globalConfiguration->offsetGet($name);
		}

		if (0 === $globalConfiguration->count())
		{
			throw new \RuntimeException('No projects defined. Add some using `project:init` command!');
		}

		$keys = array_keys($globalConfiguration->getArrayCopy());
		throw new \RuntimeException(
			'Doh! No such project. Please try one of the following:' . "\n - " . implode("\n - ", $keys)
		);
	}
}