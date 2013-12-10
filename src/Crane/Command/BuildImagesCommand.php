<?php


namespace Crane\Command;


use Crane\Configuration\ProjectRepository;
use Crane\Docker\Docker;
use Crane\Docker\Image\Image;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildImagesCommand extends AbstractBaseCommand
{
	const OPTION_REBUILD = 'rebuild';

	/** @var OutputInterface */
	private $output;

	protected function configure()
	{
		$this->setName('image:build')->setAliases(['build'])
			->setDescription('Builds docker image and all of its requirements')
			->addOption(self::OPTION_REBUILD, null, InputOption::VALUE_NONE, 'Force rebuild')
			->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name')
			->addArgument(self::ARGUMENT_TARGET, InputArgument::OPTIONAL, 'Target host');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);
		$this->output = $output;

		if (false === $docker->isDockerAvailable())
		{
			$output->writeln('<error>Cannot use docker on target</error>');
			if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
			{
				$output->writeln('<comment>' . $docker->getLastError() . '</comment>');
			}
			return;
		}

		/** @var ProjectRepository $fetcher */
		$fetcher = $this->getApplication()->getService('project-repository');
		$path = $fetcher->getProjectDirectory($image->getProjectName(true));
		$output->writeln('Copy Dockerfiles to target location…');
		$docker->copyDockerfiles($path);
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
			$this->output->writeln(sprintf('Image %s already built', $image->getName()));
			return ;
		}

		$this->output->writeln('Building image: ' . $image->getName());
		$docker->buildImage($image);
	}

}