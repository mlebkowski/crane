<?php

namespace Crane\Docker\Executor;

class SudoDecorator implements CommandDecoratorInterface
{
	/** @var CommandDecoratorInterface */
	private $parent;

	public function decorateCommand($command)
	{
		$command = $this->parent ? $this->parent->decorateCommand($command) : $command;
		return 'sudo -s -- ' . $command;
	}

	/**
	 * @param CommandDecoratorInterface $decorator
	 * @return CommandDecoratorInterface
	 */
	public function setParentDecorator(CommandDecoratorInterface $decorator)
	{
		$this->parent = $decorator;
		return $this;
	}
}