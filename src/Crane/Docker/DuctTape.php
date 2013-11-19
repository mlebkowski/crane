<?php

namespace Crane\Docker;

use Crane\Docker\Executor\CommandExecutor;
use Crane\Docker\Executor\SSHDecorator;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DuctTape
{
	public function getRemoteIp(CommandExecutor $executor)
	{
		$matches['ip'] = '127.0.0.1';
		$decorator = $executor->getDecorator();
		if ($decorator instanceof SSHDecorator)
		{
			$verbose = $decorator->getVerbose();
			$decorator->setVerbose(true);
			try
			{
				$executor->executeCommand('exit');
			}
			catch (ProcessFailedException $e)
			{
			}

			$output = $executor->getLastErrorOutput();
			$decorator->setVerbose($verbose);

			preg_match(
				'/^debug1: Connecting to [\w\d.-]+ \[(?<ip>[\d.]+)\] port \d+/m',
				$output,
				$matches
			);
		}

		return $matches['ip'];
	}
}