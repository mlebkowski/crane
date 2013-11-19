<?php

namespace Crane\Docker\Executor;

interface CommandDecoratorInterface
{
	/**
	 * @param string $command
	 * @return string
	 */
	public function decorateCommand($command);

	/**
	 * @param CommandDecoratorInterface $decorator
	 * @return CommandDecoratorInterface
	 */
	public function setParentDecorator(CommandDecoratorInterface $decorator);
}