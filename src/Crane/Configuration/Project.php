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
	/** @var ImageCollection */
	private $collection;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function getName()
	{
		return $this->data['name'];
	}
	public function getOriginalName()
	{
		return isset($this->data['original-name']) ? $this->data['original-name'] : $this->getName();
	}

	public function getTargets()
	{
		return new \ArrayObject($this->data['targets']);
	}

	public function getCommands()
	{
		$commands = isset($this->data['commands']) ? $this->data['commands'] : [];
		return new \ArrayObject(array_map(function ($item)
		{
			return isset($item['cmd']) ? $item['cmd'] : null;
		}, $commands));
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
	public function getCurrentTarget($value = false)
	{
		$name = isset($this->data['current-target']) ? $this->data['current-target'] : null;
		if ($name && $value)
		{
			return $this->getTargets()->offsetGet($name);
		}
		return $name;
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
		if ($this->collection)
		{
			return $this->collection;
		}

		$this->collection = $collection = new ImageCollection;
		$main = $this->data['main-image'];
		$repository = $this->getRepository();
		$collection->setNamespace($this->getUser());
		$collection->setProjectName($this->getName(), $this->getOriginalName());
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
						 ->setGeneric($this->isGeneric())
						 ->setPorts($settings->get('ports'), $this->getPortMapper())
						 ->setRequiredImages($settings->get('require'))
						 ->setVolumes($settings->get('volumes'))
						 ->setHostname($settings->get('hostname'))
						 ->setRepository($repository)
						 ->setIdentity($settings->get('identity'))
						 ->setUseTTY($settings->get('useTTY'))
						 ->setRemoteUser($settings->get('remoteUser', Image::DEFAULT_REMOTE_USER));
			}
			$collection->offsetSet($name, $image);
		}
		return $collection;
	}

	public function getPortMapper()
	{
		if (null === $this->portMapper)
		{
			$this->portMapper = new PortMapper($this->data['fixed-ports'], $this->getFixedPortsBase());
		}
		return $this->portMapper;
	}
	public function getFixedPorts()
	{
		return isset($this->data['fixed-ports']) ? $this->data['fixed-ports'] : [];
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

	/**
	 * @return Repository
	 */
	public function getRepository()
	{
		return new Repository(
			$this->data['repository']['target-volume'],
			$this->data['repository']['url'],
			isset($this->data['repository']['branch']) ? $this->data['repository']['branch'] : Repository::BRANCH_MASTER
		);
	}

	public function overrideBranch($branch)
	{
		$this->data['repository']['branch'] = $branch;
	}
	public function overrideName($name)
	{
		if (false === isset($this->data['original-name']))
		{
			$this->data['original-name'] = $this->data['name'];
		}
		$this->data['name'] = $name;
	}

	public function getFixedPortsBase()
	{
		return isset($this->data['fixed-ports-base']) ? $this->data['fixed-ports-base'] : null;
	}

	public function setFixedPortsBase($port)
	{
		$this->data['fixed-ports-base'] = $port;
	}

	/**
	 * Generic means it’s not a single purpose project (like for testing a branch).
	 * The project is treated as non-generic, if its name was overriden.
	 *
	 * @return bool
	 */
	public function isGeneric()
	{
		return $this->getName() === $this->getOriginalName();
	}
}