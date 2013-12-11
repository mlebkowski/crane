<?php


namespace Crane\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommandCommand extends AbstractBaseCommand
{
	const ARGUMENT_COMMANDLINE = 'commandline';
	const OPTION_COMMAND = 'cmd';

	protected function configure()
	{
		return $this->setName('project:execute')->setAliases(['execute'])
			->setDescription('Runs a command inside the main container')
			->addOption(self::OPTION_COMMAND, null, InputOption::VALUE_REQUIRED, 'Use predefined command')
			->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name. It must be running')
			->addArgument(self::ARGUMENT_COMMANDLINE, InputArgument::OPTIONAL, null);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$image = $this->getImage($input);
		$docker = $this->getDocker($input, $output);

		$docker->runInteractive($image, $this->getCommandString($input));
	}

	/**
	 * @param InputInterface $input
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	private function getCommandString(InputInterface $input)
	{
		$cmd = $input->getOption(self::OPTION_COMMAND);
		if ($cmd)
		{
			$commands = $this->getProject()->getCommands();
			if (false === $commands->offsetExists($cmd))
			{
				throw new \InvalidArgumentException('Unknown predefined command: ' . $cmd);
			}
			return $commands->offsetGet($cmd);
		}


		return $input->getArgument(self::ARGUMENT_COMMANDLINE);
	}

}