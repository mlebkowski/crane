<?php

namespace Crane\Command;

use Crane\Docker\DuctTape;
use Crane\Docker\Executor\ExecutorFactory;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupRedirectionsCommand extends Command
{
	protected function configure()
	{
		return $this->setName('setup:redirections')
			->addOption('ssh', null, InputOption::VALUE_REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$ssh = $input->getOption('ssh');
		/** @var ExecutorFactory $factory */
		$factory = $this->getApplication()->getService('executor.factory');
		$executor = $factory->createExecutor($output, true, $ssh);

		$ductTape = new DuctTape;
		var_dump($ductTape->getRemoteIp($executor));

	}

}