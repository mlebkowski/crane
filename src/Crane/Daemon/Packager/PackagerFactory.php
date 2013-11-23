<?php


namespace Crane\Daemon\Packager;

class PackagerFactory implements PackagerInterface
{
	/** @var PackagerInterface[] */
	private $packagers = [];

	/**
	 * @param PackagerInterface $packager
	 *
	 * @return $this
	 */
	public function add(PackagerInterface $packager)
	{
		$this->packagers[] = $packager;
		return $this;
	}

	/**
	 * @param string $request
	 *
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getPackage($request)
	{
		foreach ($this->packagers as $packager)
		{
			if ($packager->isHandling($request))
			{
				return $packager->getPackage($request);
			}
		}
		throw new \RuntimeException('Cannot handle ' . $request);
	}

	/**
	 * @param string $request
	 *
	 * @return bool
	 */
	public function isHandling($request)
	{
		return array_reduce($this->packagers, function ($start, PackagerInterface $packager) use ($request)
		{
			return $start || $packager->isHandling($request);
		});
	}}