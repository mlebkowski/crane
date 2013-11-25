<?php

namespace Crane\Docker\Executor;

class IdentityDecorator implements CommandDecoratorInterface
{
	/**
	 * @param string $command
	 * @return string
	 */
	public function decorateCommand($command, CommandExecutor $executor)
	{
		return $command;
	}

	/**
	 * @param CommandDecoratorInterface $decorator
	 * @return CommandDecoratorInterface
	 */
	public function setParentDecorator(CommandDecoratorInterface $decorator)
	{
		// noop
	}
}