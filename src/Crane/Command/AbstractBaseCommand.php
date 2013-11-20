<?php


namespace Crane\Command;


use Crane\Docker\Docker;
use Crane\Docker\Executor\ExecutorFactory;
use Crane\Docker\Image\ImageCollection;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractBaseCommand extends Command
{
	const ARGUMENT_NAME = 'name';
	const OPTION_SSH = 'ssh';

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
	protected function getImage(InputInterface $input)
	{
		$name = $input->getArgument(self::ARGUMENT_NAME) ?: $this->getApplication()->getService('Docker')['Main'];
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

}