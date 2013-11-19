<?php

namespace Crane\Docker\Executor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CommandExecutor
{
	/** @var  InputInterface */
	private $input;
	/** @var  OutputInterface */
	private $output;

	/**
	 * @var Process
	 */
	private $process;

	/** @var CommandDecoratorInterface */
	private $decorator;

	public function __construct(CommandDecoratorInterface $decorator = null)
	{
		$this->decorator = $decorator ?: new IdentityDecorator;
	}

	public function executeCommand($command, $stdIn = null)
	{
		$command = $this->decorator->decorateCommand($command);
		$this->process = $process = new Process($command);
		$process->setTimeout(null);
		$process->setTty(true);
		if (null !== $stdIn)
		{
			$process->setStdin($stdIn);
		}
		$output = $this->getOutput();
		if ($output && $output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
		{
			$output->writeln(sprintf('$ %s', $command));
			$start = true;
			$prefix = ' ---> ';
			$process->start(function ($type, $buffer) use ($output, &$start, $prefix)
			{
				if ($start)
				{
					$buffer = $prefix . $buffer;
					$start = false;
				}

				if ($buffer[strlen($buffer)-1] == "\n")
				{
					$start = true;
					$buffer = strtr(substr($buffer, 0, -1), ["\n" => "\n".$prefix]) . "\n";
				}
				else
				{
					$buffer = strtr($buffer, ["\n" => "\n".$prefix]);
				}
				$output->write($buffer);
			});
			$process->wait();
		}
		else
		{
			$process->run();
		}

		if ($process->getExitCode() != 0)
		{
			throw new ProcessFailedException($process);
		}
		return $process->getOutput();
	}

	/**
	 * @param CommandDecoratorInterface $decorator
	 * @param bool $chain
	 */
	public function setDecorator(CommandDecoratorInterface $decorator, $chain = true)
	{
		$this->decorator = $chain ? $decorator->setParentDecorator($this->decorator) : $decorator;
	}

	/**
	 * @return CommandDecoratorInterface
	 */
	public function getDecorator()
	{
		return $this->decorator;
	}

	public function getLastErrorOutput()
	{
		return $this->process ? $this->process->getErrorOutput() : null;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @return $this
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
		return $this;
	}

	/**
	 * @return \Symfony\Component\Console\Input\InputInterface
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return $this
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
		return $this;
	}

	/**
	 * @return \Symfony\Component\Console\Output\OutputInterface
	 */
	public function getOutput()
	{
		return $this->output;
	}

}