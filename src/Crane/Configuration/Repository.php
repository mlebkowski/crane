<?php


namespace Crane\Configuration;


class Repository
{
	private $url;
	private $targetVolume;

	public function __construct($targetVolume, $url)
	{
		$this->targetVolume = $targetVolume;
		$this->url = $url;
	}

	public function isRoot($volume)
	{
		return $this->targetVolume === $volume;
	}

	/**
	 * @return mixed
	 */
	public function getTargetVolume()
	{
		return $this->targetVolume;
	}

	/**
	 * @return mixed
	 */
	public function getUrl()
	{
		return $this->url;
	}

}