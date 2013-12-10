<?php


namespace Crane\Docker;


class PortMapper
{
	const FIXED_RANGE_START = 49000;
	const FIXED_RANGE_END = 49150;

	private $ports;
	/**
	 * @var null
	 */
	private $base;

	public function __construct($ports, $base = null)
	{
		$this->ports = array_values($ports);
		$this->base = $base;
	}

	public function isPortMapped($portSpec)
	{
		return in_array($portSpec, $this->ports);
	}

	public function mapPort($portSpec, System\User $user)
	{
		if (false === $this->isPortMapped($portSpec))
		{
			return null;
		}

		$idx = array_search($portSpec, $this->ports);
		if (null === $this->base)
		{
			$slotSize = sizeof($this->ports);
			$slotsCount = floor((self::FIXED_RANGE_END - self::FIXED_RANGE_START) / $slotSize);
			$slot = $user->getId() % $slotsCount;
			$start = self::FIXED_RANGE_START + $slot * $slotSize;
		}
		else
		{
			$start = $this->base;
		}

		return $start + $idx;
	}
}