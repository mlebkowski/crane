<?php


namespace Crane\Docker\System;


class User
{
	/** @var string */
	private $name;
	/** @var int */
	private $id;
	/** @var string */
	private $home;

	function __construct($id, $name, $home = null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->home = $home ?: '/home/' . $name;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getHome()
	{
		return $this->home;
	}

}