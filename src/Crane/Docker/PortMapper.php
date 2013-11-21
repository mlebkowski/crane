<?php


namespace Crane\Docker;


class PortMapper
{
	const FIXED_RANGE_START = 49000;
	const FIXED_RANGE_END = 49150;

	private $ports;

	public function __construct($ports)
	{
		$this->ports = array_values($ports);
	}

	public function isPortMapped($port)
	{
		return in_array($port, $this->ports);
	}

	public function mapPort($port, System\User $user)
	{
		if (false === $this->isPortMapped($port))
		{
			return null;
		}

		$idx = array_search($port, $this->ports);
		$slotSize = sizeof($this->ports);
		$slotsCount = floor((self::FIXED_RANGE_END - self::FIXED_RANGE_START) / $slotSize);
		$slot = $user->getId() % $slotsCount;

		return self::FIXED_RANGE_START + $slot * $slotSize + $idx;
	}
}