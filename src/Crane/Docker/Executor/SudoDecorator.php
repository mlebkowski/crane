<?php

namespace Crane\Docker\Executor;

class SudoDecorator implements CommandDecoratorInterface
{
	/**
	 * @var array
	 */
	private $onlyForCommands;

	public function __construct(array $onlyForCommands = [])
	{
		$this->onlyForCommands = $onlyForCommands;
	}

	/** @var CommandDecoratorInterface */
	private $parent;

	public function decorateCommand($command)
	{
		$command = $this->parent ? $this->parent->decorateCommand($command) : $command;
		if ($this->onlyForCommands)
		{
			list ($arg) = explode(' ', $command);
			if (false === in_array($arg, $this->onlyForCommands))
			{
				return $command;
			}
		}
		return 'sudo -- ' . $command;
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