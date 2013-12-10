<?php

namespace Crane\Command;

use Crane\Configuration\AssetsLocatorInterface;
use Crane\Docker\Docker;
use Crane\Docker\Image\Image;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartProjectCommand extends AbstractBaseCommand
{
	const OPTION_RESTART = 'restart';
	/** @var OutputInterface */
	private $output;

	/** @var AssetsLocatorInterface */
	private $locator;

	protected function configure()
	{
		return $this->setName('project:start')->setAliases(['start'])
			->addOption(self::OPTION_RESTART, null, InputOption::VALUE_NONE, 'Restart running instances')
			->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name')
			->addArgument(self::ARGUMENT_TARGET, null, 'Use this target', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->locator = $this->getApplication()->getService('project-repository');
		$this->output = $output;

		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);

		$output->writeln('Starting docker containers…');
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
			$this->output->writeln(sprintf('Starting new %s instance', $image->getName()));
			$container = $docker->startImage($image, $this->locator);
		}
		elseif (false === $container->isRunning() || $image->isMain() || $restart)
		{
			$this->output->writeln(sprintf('Restarting %s instance', $image->getName()));
			$docker->remove($container);
			$container = $docker->startImage($image, $this->locator);
		}

		return $container;
	}

}