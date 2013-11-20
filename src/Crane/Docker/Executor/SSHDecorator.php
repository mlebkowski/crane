<?php

namespace Crane\Docker\Executor;

class SSHDecorator implements CommandDecoratorInterface
{
	/** @var CommandDecoratorInterface */
	private $parent;
	private $host = null;
	private $user = null;
	private $port = null;
	private $identityFile = null;
	private $verbose = false;

	public function __construct($host)
	{
		$this->setHost($host);
	}

	/**
	 * @param boolean $verbose
	 */
	public function setVerbose($verbose)
	{
		$this->verbose = (bool) $verbose;
	}

	/**
	 * @return boolean
	 */
	public function getVerbose()
	{
		return $this->verbose;
	}


	/**
	 * @param string $host
	 * @return $this
	 */
	public function setHost($host)
	{
		$this->host = $host;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @param string $identityFile
	 * @return $this
	 */
	public function setIdentityFile($identityFile)
	{
		$this->identityFile = $identityFile;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentityFile()
	{
		return $this->identityFile;
	}

	/**
	 * @param int $port
	 * @return $this
	 */
	public function setPort($port)
	{
		$this->port = (int) $port;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @param string $user
	 * @return $this
	 */
	public function setUser($user)
	{
		$this->user = $user;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	public function decorateCommand($command)
	{
		$command = $this->parent ? $this->parent->decorateCommand($command) : $command;
		$sshCommand = 'ssh -A';

		if ($this->getVerbose())
		{
			$sshCommand .= ' -v';
		}
		if ($this->getPort())
		{
			$sshCommand .= ' -p ' . $this->getPort();
		}
		if ($this->getIdentityFile())
		{
			$sshCommand .= ' -i ' . escapeshellarg($this->getIdentityFile());
		}

		if ($this->getUser())
		{
			$target = escapeshellarg($this->getUser() . '@' . $this->getHost());
		}
		else
		{
			$target = escapeshellarg($this->getHost());
		}

		return sprintf("%s %s %s", $sshCommand, $target, escapeshellarg($command));
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