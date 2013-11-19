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
	 * @var CommandExecutor
	 */
	private $parentExecutor;

	/**
	 * @var Process
	 */
	private $process;

	/**
	 * @return CommandExecutor
	 */
	public function getRootExecutor()
	{
		return $this->parentExecutor ? $this->getParentExecutor()->getRootExecutor() : $this;
	}

	/**
	 * @param \Crane\Docker\Executor\CommandExecutor $parentExecutor
	 */
	public function setParentExecutor(CommandExecutor $parentExecutor)
	{
		$this->parentExecutor = $parentExecutor;
	}

	/**
	 * @return \Symfony\Component\Process\Process
	 */
	public function getLastProcess()
	{
		if ($this->getRootExecutor() === $this)
		{
			return $this->process;
		}
		return $this->getRootExecutor()->getLastProcess();
	}

	/**
	 * @return \Crane\Docker\Executor\CommandExecutor
	 */
	protected function getParentExecutor()
	{
		return $this->parentExecutor;
	}

	public function executeCommand($command, $stdin = null)
	{
		$this->process = $process = new Process($command);
		if (null !== $stdin)
		{
			$process->setStdin($stdin);
		}
		$output = $this->getOutput();
		//		$process->setTty(true);
		if ($output && $output->getVerbosity() >= $output::VERBOSITY_VERBOSE)
		{
			$output->writeln(sprintf('$ %s', $command));
			$process->start(function ($type, $buffer) use ($output)
			{
				$buffer = preg_replace('/^(?=.)/m', ' --> ', $buffer);
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
		if ($this->getRootExecutor() === $this)
		{
			$this->output = $output;
		}
		else
		{
			$this->getRootExecutor()->setOutput($output);
		}
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