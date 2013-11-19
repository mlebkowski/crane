<?php

namespace Crane\Docker\Image;

class ImageCollection extends \ArrayObject
{
	private $namespace;

	/**
	 * @param mixed $index
	 * @return Image
	 */
	public function offsetGet($index)
	{
		return parent::offsetGet($index);
	}

	/**
	 * @param mixed $index
	 * @param Image $value
	 * @throws \InvalidArgumentException
	 */
	public function offsetSet($index, $value)
	{
		if (false === ($value instanceof Image))
		{
			throw new \InvalidArgumentException('Value is not an instance of Image');
		}
		$value->setCollection($this);
		parent::offsetSet($index, $value);
	}

	/**
	 * @param Image $value
	 * @throws \InvalidArgumentException
	 */
	public function append($value)
	{
		if (false === ($value instanceof Image))
		{
			throw new \InvalidArgumentException('Value is not an instance of Image');
		}
		$value->setCollection($this);
		parent::append($value);
	}

	/**
	 * @return Image[]
	 */
	public function getArrayCopy()
	{
		return parent::getArrayCopy();
	}

	/**
	 * @param Image[] $input
	 * @throws \InvalidArgumentException
	 * @return Image[]|void
	 */
	public function exchangeArray($input)
	{
		foreach ($input as $idx => $value)
		{
			if (false === ($value instanceof Image))
			{
				throw new \InvalidArgumentException("Value[$idx] is not an instance of Image");
			}
			$value->setCollection($this);
		}
		return parent::exchangeArray($input);
	}

	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}
	public function getNamespace()
	{
		return $this->namespace;
	}


}