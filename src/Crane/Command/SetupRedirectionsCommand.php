<?php

namespace Crane\Command;

use Cilex\Provider\Console\ContainerAwareApplication;
use Crane\Docker\DuctTape;
use Crane\Docker\Executor\ExecutorFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupRedirectionsCommand extends AbstractBaseCommand
{
	private $version;
	private $remoteIp;

	protected function configure()
	{
		return $this->setName('setup:redirections')->setAliases(['setup'])
			   ->addArgument(self::ARGUMENT_NAME, InputArgument::REQUIRED, 'Project name');

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->getImage($input);
		$this->getDocker($input, $output);

		/** @var ExecutorFactory $factory */
		$factory = $this->getApplication()->getService('executor.factory');
		$executor = $factory->createExecutor($output, true, $this->getProject()->getCurrentTarget(true));

		$ductTape = new DuctTape;
		$this->remoteIp = $ductTape->getRemoteIp($executor);

		/** @var ContainerAwareApplication $console */
		$console = $this->getApplication()->getService('console');
		$this->version = strip_tags($console->getLongVersion());

		$dpHosts = ['local.znanylekarz.pl', 'local.znamylekar.pl', 'local.docplanner.ru'];

		$hosts = file('/etc/hosts');
		$hosts = array_map(function ($line) use ($dpHosts)
		{
			if ("#" === substr(ltrim($line), 0, 1) || "" == ltrim($line))
			{
				return $line;
			}
			list (, $host) = preg_split('/\s+/', trim($line));
			if (false === in_array($host, $dpHosts))
			{
				return $line;
			}

			return "";
		}, $hosts);

		while (trim($hosts[sizeof($hosts)-1]) == "")
		{
			$hosts = array_slice($hosts, 0, -1);
		}

		$hosts[] = "\n";
		foreach ($dpHosts as $host)
		{
			$hosts[] = $this->getLine($host);
		}
		file_put_contents("/etc/hosts", implode("", $hosts));
	}

	protected function getLine($host)
	{
		return sprintf("%s	%s	# %s\n", $this->remoteIp, $host, $this->version);
	}

}