<?php

namespace Crane\Docker\Executor;

class NullDecorator implements CommandDecoratorInterface
{
	/**
	 * @param string $command
	 * @return string
	 */
	public function decorateCommand($command)
	{
		return "";
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