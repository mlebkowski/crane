<?php

namespace Crane\Docker\Executor;

class SSHExecutor extends CommandExecutor
{
	private $host = null;
	private $user = null;
	private $port = 22;
	private $identityFile = null;

	public function __construct($host, CommandExecutor $parentExecutor = null)
	{
		$this->setHost($host);
		$this->setParentExecutor($parentExecutor ?: new CommandExecutor);
	}

	public function executeCommand($command, $stdin = null)
	{
		$this->getParentExecutor()->executeCommand($this->getSSHCommand($command), $stdin);
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

	private function getSSHCommand($command)
	{
		$sshCommand = 'ssh' ;
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
}