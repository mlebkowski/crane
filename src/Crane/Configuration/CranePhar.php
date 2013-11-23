<?php

namespace Crane\Configuration;

class CranePhar extends \PharData
{
	public function getJson()
	{
		$craneJson = file_get_contents($this['crane.json']);
		return json_decode($craneJson, true);
	}

}