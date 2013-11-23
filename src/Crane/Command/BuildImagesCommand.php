<?php


namespace Crane\Command;


use Crane\Docker\Docker;
use Crane\Docker\Image\Image;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildImagesCommand extends AbstractBaseCommand
{
	const OPTION_REBUILD = 'rebuild';

	protected function configure()
	{
		$this->setName('image:build')
			->setDescription('Builds docker image and all of its requirements')
			->addOption(self::OPTION_REBUILD, null, InputOption::VALUE_NONE, 'Force rebuild')
			->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name')
			->addArgument(self::ARGUMENT_TARGET, InputArgument::OPTIONAL, 'Target host');
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
		$this->buildImageWithRequirements($image, $docker, $input->getOption(self::OPTION_REBUILD));
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