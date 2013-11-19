<?php


namespace Crane\Docker\Executor;


use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutorFactory
{
	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @param bool                                              $useSudo
	 * @param string                                            $ssh
	 *
	 * @return CommandExecutor|SSHExecutor|SudoExecutor
	 */
	public function createExecutor(OutputInterface $output, $useSudo = false, $ssh = null)
	{
		$executor = new CommandExecutor;
		$executor->setOutput($output);

		if ($ssh)
		{
			$user = null;
			$port = null;
			$host = $ssh;
			if (strpos($host, '@') !== false)
			{
				list ($user, $host) = explode('@', $host);
			}
			list ($host, $port) = array_pad(explode(':', $host), 2, null);
			$executor = new SSHExecutor($host, $executor);
			if ($user)
			{
				$executor->setUser($user);
				if ('vagrant' === $user)
				{
					$executor->setIdentityFile(getenv('HOME') . '/.vagrant.d/insecure_private_key');
				}
			}
			if ($port)
			{
				$executor->setPort($port);
			}
		}
		if ($useSudo)
		{
			$executor = new SudoExecutor($executor);
		}

		return $executor;
	}

}