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
		$container = $this->startImagesWithRequirements($image, $docker, $input->getOption(self::OPTION_RESTART));

		$output->writeln(sprintf('<info>http://local.znanylekarz.pl:%s/</info>', $container->getExposedPort(80)));
	}

	/**
	 * @param Image  $image
	 * @param Docker $docker
	 * @param bool   $restart
	 *
	 * @return \Crane\Docker\DockerContainer
	 */
	private function startImagesWithRequirements(Image $image, Docker $docker, $restart = false)
	{
		if (false === $image->isRunnable())
		{
			return null;
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
		elseif (false === $container->isRunning() || $image->isMain() || $restart)
		{
			$docker->remove($container);
			$container = $docker->startImage($image);
		}

		return $container;
	}

}