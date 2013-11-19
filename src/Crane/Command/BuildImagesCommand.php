<?php


namespace Crane\Command;


use Crane\Docker\Docker;
use Crane\Docker\Executor\ExecutorFactory;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class BuildImagesCommand extends Command
{
	const ARGUMENT_NAME = 'name';
	const OPTION_SSH = 'ssh';

	protected function configure()
	{
		$this->setName('image:build')
			->setDescription('Builds docker image and all of its requirements')
			->addOption(self::OPTION_SSH, null, InputOption::VALUE_REQUIRED, 'Execute commands on target host')
			->addArgument(self::ARGUMENT_NAME, null, 'Image name', 'web');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument(self::ARGUMENT_NAME);
		$image = $this->getImageByName($name);

		if (null === $image)
		{
			throw new \RuntimeException('Doh! No such image. Please try one of the following:' . "\n - "
				. implode("\n - ", array_keys($this->getApplication()->getService('images'))));
		}

		$ssh = $input->getOption(self::OPTION_SSH);
		/** @var ExecutorFactory $factory */
		$factory = $this->getApplication()->getService('executor.factory');
		$executor = $factory->createExecutor($output, true, $ssh);

		/** @var Docker $docker */
		$docker = $this->getApplication()->getService('docker');
		$docker->setExecutor($executor);

		if (false === $docker->isDockerAvailable())
		{
			$output->writeln('<error>Cannot use docker on target</error>');
			if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
			{
				$output->writeln('<comment>' . $executor->getLastProcess()->getErrorOutput() . '</comment>');
			}
			return;
		}

		$docker->copyDockerfiles();

	}

	private function getImageByName($name)
	{
		$images = $this->getApplication()->getService('images');
		if (isset($images[$name]))
		{
			return new ParameterBag((array) $images[$name]);
		}
		return null;
	}

}