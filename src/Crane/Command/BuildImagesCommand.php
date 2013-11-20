<?php


namespace Crane\Command;


use Crane\Docker\Docker;
use Crane\Docker\Image\Image;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildImagesCommand extends AbstractBaseCommand
{
	const ARGUMENT_NAME = 'name';
	const OPTION_SSH = 'ssh';

	protected function configure()
	{
		$this->setName('image:build')
			->setDescription('Builds docker image and all of its requirements')
			->addOption(self::OPTION_SSH, null, InputOption::VALUE_REQUIRED, 'Execute commands on target host')
			->addArgument(self::ARGUMENT_NAME, null, 'Image name', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);

		if (false === $docker->isDockerAvailable())
		{
			$output->writeln('<error>Cannot use docker on target</error>');
			if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
			{
				$output->writeln('<comment>' . $docker->getLastError() . '</comment>');
			}
			return;
		}

		$docker->copyDockerfiles();
		$this->buildImageWithRequirements($image, $docker);
	}


	private function buildImageWithRequirements(Image $image, Docker $docker, $force = false)
	{
		foreach ($image->getRequiredImages() as $depImage)
		{
			$this->buildImageWithRequirements($depImage, $docker, $force);
		}

		if ($docker->isImageBuilt($image) && !$force)
		{
			return ;
		}

		$docker->buildImage($image);
	}

}