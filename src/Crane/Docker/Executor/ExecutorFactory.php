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
	 * @return CommandExecutor
	 */
	public function createExecutor(OutputInterface $output, $useSudo = false, $ssh = null)
	{
		$executor = new CommandExecutor;
		$executor->setOutput($output);

		if ($useSudo)
		{
			$executor->setDecorator(new SudoDecorator(['docker']));
		}

		$decorator = $this->getSSHDecorator($ssh);
		if ($decorator)
		{
			$executor->setDecorator($decorator);
		}

		return $executor;
	}

	/**
	 * @param $ssh
	 * @return SSHDecorator
	 */
	private function getSSHDecorator($ssh)
	{
		if (!$ssh)
		{
			return null;
		}

		$user = null;
		$port = null;
		$host = $ssh;

		if (strpos($host, '@') !== false)
		{
			list ($user, $host) = explode('@', $host);
		}

		list ($host, $port) = array_pad(explode(':', $host), 2, null);

		$decorator = new SSHDecorator($host);
		if ($user)
		{
			$decorator->setUser($user);
			if ('vagrant' === $user)
			{
				$decorator->setIdentityFile(getenv('HOME') . '/.vagrant.d/insecure_private_key');
			}
		}

		if ($port)
		{
			$decorator->setPort($port);
		}

		return $decorator;
	}

}