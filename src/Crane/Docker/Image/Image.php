<?php

namespace Crane\Docker\Image;

class Image
{
	/** @var string */
	protected $name;
	/** @var array */
	protected $ports;
	/** @var ImageCollection|array */
	protected $requiredImages;
	/** @var array */
	protected $volumes;

	/** @var ImageCollection */
	private $collection;

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getFullName()
	{
		return sprintf('%s/%s', $this->getCollection()->getNamespace(), $this->getName());
	}

	/**
	 * @param array $ports
	 */
	public function setPorts($ports)
	{
		$this->ports = $ports;
	}

	/**
	 * @return array
	 */
	public function getPorts()
	{
		return $this->ports;
	}

	/**
	 * @param \Crane\Docker\Image\ImageCollection|array $requiredImages
	 */
	public function setRequiredImages($requiredImages)
	{
		$this->requiredImages = $requiredImages;
	}

	/**
	 * @return \Crane\Docker\Image\ImageCollection
	 */
	public function getRequiredImages()
	{
		if (false === ($this->requiredImages instanceof ImageCollection))
		{
			$this->requiredImages = array_reduce($this->requiredImages,
				function (ImageCollection $collection, $name)
				{
					$collection->append($this->getCollection()->offsetGet($name));
					return $collection;
				},
			new ImageCollection);
		}
		return $this->requiredImages;
	}

	/**
	 * @param array $volumes
	 */
	public function setVolumes($volumes)
	{
		$this->volumes = $volumes;
	}

	/**
	 * @return array
	 */
	public function getVolumes()
	{
		return $this->volumes;
	}

	/**
	 * @param \Crane\Docker\Image\ImageCollection $collection
	 */
	public function setCollection($collection)
	{
		// set collection only once!
		$this->collection = $this->collection ?: $collection;
	}

	/**
	 * @return \Crane\Docker\Image\ImageCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}



}