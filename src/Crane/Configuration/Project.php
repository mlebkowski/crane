<?php


namespace Crane\Configuration;


use Crane\Docker\Image\Image;
use Crane\Docker\Image\ImageCollection;
use Crane\Docker\PortMapper;
use Symfony\Component\HttpFoundation\ParameterBag;

class Project implements \JsonSerializable
{
	/** @var PortMapper */
	private $portMapper;
	/**
	 * @var array
	 */
	private $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function getName()
	{
		return $this->data['name'];
	}

	public function getTargets()
	{
		return new \ArrayObject($this->data['targets']);
	}

	public function useDefaultTarget()
	{
		list ($target) = array_pad(array_values($this->getTargets()->getArrayCopy()), 1, null);
		$this->setCurrentTarget($target);
	}

	public function setCurrentTarget($target)
	{
		$this->data['current-target'] = $target;
	}
	public function getCurrentTarget()
	{
		return isset($this->data['current-target']) ? $this->data['current-target'] : null;
	}
	public function hasCurrentTarget()
	{
		return array_key_exists('current-target', $this->data);
	}

	/**
	 * @return ImageCollection
	 */
	public function getImages()
	{
		$collection = new ImageCollection;
		$main = $this->data['main-image'];
		$repository = new Repository($this->data['repository']['target-volume'], $this->data['repository']['url']);
		$collection->setNamespace($this->getUser());
		$collection->setProjectName($this->getName());
		foreach ($this->data['images'] as $name => $settings)
		{
			if (null === $settings)
			{
				$image = (new Image($name))->setRunnable(false);
			}
			else
			{
				$settings = new ParameterBag((array) $settings);
				/** @var Image $image */
				$image = (new Image($name))
						 ->setMain($main === $name)
						 ->setPorts($settings->get('ports'), $this->getPortMapper())
						 ->setRequiredImages($settings->get('require'))
						 ->setVolumes($settings->get('volumes'))
						 ->setHostname($settings->get('hostname'))
						 ->setRepository($repository)
						 ->setUseTTY($settings->get('useTTY'));
			}
			$collection->offsetSet($name, $image);
		}
		return $collection;
	}

	public function getPortMapper()
	{
		if (null === $this->portMapper)
		{
			$this->portMapper = new PortMapper($this->data['fixed-ports']);
		}
		return $this->portMapper;
	}

	public function jsonSerialize()
	{
		return $this->data;
	}

	/**
	 * @return Image
	 */
	public function getMainImage()
	{
		return $this->getImages()->offsetGet($this->data['main-image']);
	}

	public function getUser()
	{
		return $this->data['user'];
	}
}