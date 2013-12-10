<?php

namespace Crane\Docker\Image;

use Crane\Configuration\Repository;
use Crane\Docker\PortMapper;

class Image
{
	const DEFAULT_REMOTE_USER = 'root';
	private $generic = true;
	/** @var string */
	private $remoteUser = self::DEFAULT_REMOTE_USER;
	/** @var Repository */
	private $repository;
	/** @var PortMapper */
	private $portMapper;
	/** @var bool */
	private $main;
	/** @var string */
	protected $name;
	/** @var array */
	protected $ports = [];
	/** @var ImageCollection|array */
	protected $requiredImages = [];
	/** @var array */
	protected $volumes = [];
	/** @var bool */
	protected $useTTY = false;
	/** @var string */
	protected $hostname;
	/** @var string */
	protected $identity;
	/** @var bool */
	private $runnable = true;

	/** @var ImageCollection */
	private $collection;

	public function __construct($name)
	{
		$this->setName($name);
	}

	public function setGeneric($generic)
	{
		$this->generic = (bool) $generic;
		return $this;
	}
	public function isGeneric()
	{
		return $this->generic;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
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
	public function getProjectName($useOriginal = false)
	{
		return $this->getCollection()->getProjectName($useOriginal);
	}

	/**
	 * @param array                    $ports
	 * @param \Crane\Docker\PortMapper $portMapper
	 *
	 * @return $this
	 */
	public function setPorts($ports, PortMapper $portMapper = null)
	{
		$this->ports = array_filter(array_map('intval', (array) $ports));
		$this->portMapper = $portMapper;
		return $this;
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
	 * @return $this
	 */
	public function setRequiredImages($requiredImages)
	{
		$this->requiredImages = (array) $requiredImages;
		return $this;
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
	 * @return $this
	 */
	public function setVolumes($volumes)
	{
		$this->volumes = (array) $volumes;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getVolumes()
	{
		return $this->volumes;
	}

	/**
	 * @param boolean $useTTY
	 * @return $this
	 */
	public function setUseTTY($useTTY)
	{
		$this->useTTY = (bool) $useTTY;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getUseTTY()
	{
		return $this->useTTY;
	}

	/**
	 * @param string $hostname
	 * @return $this
	 */
	public function setHostname($hostname)
	{
		$this->hostname = $hostname;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHostname()
	{
		return $this->hostname;
	}

	/**
	 * @param boolean $runnable
	 * @return $this
	 */
	public function setRunnable($runnable)
	{
		$this->runnable = (bool) $runnable;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isRunnable()
	{
		return $this->runnable;
	}

	/**
	 * @param $main
	 * @return $this
	 */
	public function setMain($main)
	{
		$this->main = (bool) $main;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isMain()
	{
		return $this->main;
	}

	/**
	 * @param \Crane\Docker\Image\ImageCollection $collection
	 * @return $this
	 */
	public function setCollection($collection)
	{
		// set collection only once!
		$this->collection = $this->collection ?: $collection;
		return $this;
	}

	/**
	 * @return \Crane\Docker\Image\ImageCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	/**
	 * @param string $user
	 *
	 * @return string
	 */
	public function getRunningName($user)
	{
		$projectName = str_replace('/', '_', $this->getProjectName());
		if ($this->isGeneric())
		{
			return sprintf('%s_%s_%s', $user, $projectName, $this->getName());
		}
		return sprintf('%s_%s', $projectName, $this->getName());
	}

	/**
	 * @return PortMapper
	 */
	public function getPortMapper()
	{
		return $this->portMapper;
	}

	public function isVolumeGitRoot($volume)
	{
		return $this->repository->isRoot($volume);
	}

	/**
	 * @param Repository $repository
	 * @return $this
	 */
	public function setRepository(Repository $repository)
	{
		$this->repository = $repository;
		return $this;
	}

	/**
	 * @return Repository
	 */
	public function getRepository()
	{
		return $this->repository;
	}

	/**
	 * @param string $identity
	 * @return $this
	 */
	public function setIdentity($identity)
	{
		$this->identity = $identity;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentity()
	{
		return $this->identity;
	}

	/**
	 * @param string $user
	 *
	 * @return $this
	 */
	public function setRemoteUser($user)
	{
		$this->remoteUser = $user;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRemoteUser()
	{
		return $this->remoteUser;
	}

}