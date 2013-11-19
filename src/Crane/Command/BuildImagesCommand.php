<?php


namespace Crane\Command;


use Crane\Docker\Docker;
use Crane\Docker\Executor\ExecutorFactory;
use Crane\Docker\Image\Image;
use Crane\Docker\Image\ImageCollection;
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

	/**
	 * @param string $name
	 * @return \Crane\Docker\Image\Image
	 */
	private function getImageByName($name)
	{
		/** @var ImageCollection $images */
		$images = $this->getApplication()->getService('images');
		return $images->offsetGet($name);
	}

	/**
	 * @param InputInterface $input
	 * @return \Crane\Docker\Image\Image
	 * @throws \RuntimeException
	 */
	private function getImage(InputInterface $input)
	{
		$name = $input->getArgument(self::ARGUMENT_NAME);
		$image = $this->getImageByName($name);

		if (null === $image) {
			$keys = array_keys($this->getApplication()->getService('images')->getArrayCopy());
			throw new \RuntimeException(
				'Doh! No such image. Please try one of the following:' . "\n - " . implode("\n - ", $keys)
			);
		}
		return $image;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return Docker
	 */
	protected function getDocker(InputInterface $input, OutputInterface $output)
	{
		$ssh = $input->getOption(self::OPTION_SSH);
		/** @var ExecutorFactory $factory */
		$factory = $this->getApplication()->getService('executor.factory');
		$executor = $factory->createExecutor($output, true, $ssh);

		/** @var Docker $docker */
		$docker = $this->getApplication()->getService('docker');
		$docker->setExecutor($executor);

		return $docker;
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