<?php

namespace Crane\Command;

use Crane\Docker\Docker;
use Crane\Docker\Image\Image;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartContainerCommand extends AbstractBaseCommand
{
	const OPTION_RESTART = 'restart';

	protected function configure()
	{
		return $this->setName('container:start')
			->addOption(self::OPTION_SSH, null, InputOption::VALUE_REQUIRED, 'Execute commands on target host')
			->addOption(self::OPTION_RESTART, null, InputOption::VALUE_NONE, 'Restart running instances')
			->addArgument(self::ARGUMENT_NAME, null, 'Image name', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);
		$this->startImagesWithRequirements($image, $docker, $input->getOption(self::OPTION_RESTART));

	}

	private function startImagesWithRequirements(Image $image, Docker $docker, $restart = false)
	{
		if (false === $image->isRunnable())
		{
			return ;
		}

		foreach ($image->getRequiredImages() as $dep)
		{
			$this->startImagesWithRequirements($dep, $docker, $restart);
		}

		$container = $docker->getDockerContainer($image);
		if (false === $container->exists())
		{
			$container = $docker->startImage($image);
		}
		elseif (false === $container->isRunning() || $restart)
		{
			$docker->remove($container);
			$container = $docker->startImage($image);
		}

		print_r([
			$image->getName() => $container->getFirstExposedPort()
		]);
	}

}