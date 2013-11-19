<?php


namespace Crane\Docker\Executor;

class SudoExecutor extends CommandExecutor
{
	public function __construct(CommandExecutor $parentExecutor = null)
	{
		$this->setParentExecutor($parentExecutor?: new CommandExecutor);
	}

	public function executeCommand($command, $stdin = null)
	{
		return $this->getParentExecutor()->executeCommand('sudo ' . $command, $stdin);
	}
}