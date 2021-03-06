<?php

namespace Crane\Docker\Executor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\TTY;

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

	private $workingDirectoriesStack = [];

	private $tty;

	public function __construct(CommandDecoratorInterface $decorator = null)
	{
		$this->decorator = $decorator ?: new IdentityDecorator;
	}

	public function executeCommand($command, $stdIn = null, $quiet = false)
	{
		$this->tty = $stdIn instanceof TTY;

		$command = $this->decorator->decorateCommand($command, $this);
		$this->process = $process = new Process($command, array_shift($this->workingDirectoriesStack));
		$process->setTimeout(null);
		if (null !== $stdIn)
		{
			if ($this->tty)
			{
				$process->setTty(true);
			}
			else
			{
				$process->setStdin($stdIn);
			}
		}
		$output = $this->getOutput();
		if ($output && $output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
		{
			$output->writeln(sprintf('$ %s', $command));
			$start = true;
			$prefix = ' ---> ';
			$process->start(function ($type, $buffer) use ($output, &$start, $prefix, $quiet)
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
				$quiet || $output->write($buffer);
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
	 * @param string $directory
	 *
	 * @return $this
	 */
	public function cwd($directory)
	{
		$this->workingDirectoriesStack[] = $directory;
		return $this;
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

	/**
	 * @return mixed
	 */
	public function getTty()
	{
		return $this->tty;
	}

}