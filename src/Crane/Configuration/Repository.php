<?php


namespace Crane\Configuration;


class Repository
{
	const BRANCH_MASTER = 'master';
	/**
	 * @var string
	 */
	private $url;
	/**
	 * @var string
	 */
	private $targetVolume;
	/**
	 * @var string
	 */
	private $branch;

	public function __construct($targetVolume, $url, $branch = self::BRANCH_MASTER)
	{
		$this->targetVolume = $targetVolume;
		$this->url = $url;
		$this->branch = $branch;
	}

	/**
	 * @param string $volume
	 *
	 * @return bool
	 */
	public function isRoot($volume)
	{
		return $this->targetVolume === $volume;
	}

	/**
	 * @return string
	 */
	public function getTargetVolume()
	{
		return $this->targetVolume;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getBranch()
	{
		return $this->branch;
	}

}