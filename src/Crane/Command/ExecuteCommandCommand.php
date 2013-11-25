<?php


namespace Crane\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommandCommand extends AbstractBaseCommand
{
	const ARGUMENT_COMMANDLINE = 'commandline';

	protected function configure()
	{
		return $this->setName('project:command')
			->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name. It must be running')
			->addArgument(self::ARGUMENT_COMMANDLINE, InputArgument::IS_ARRAY | InputArgument::OPTIONAL, null, ['bash']);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);

		$docker->runInteractive($image);
	}

}