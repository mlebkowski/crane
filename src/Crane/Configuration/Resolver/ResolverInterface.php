<?php

namespace Crane\Configuration\Resolver;

use Crane\Configuration\CranePhar;

interface ResolverInterface
{
	/**
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function isHandling($uri);

	/**
	 * @param string $uri
	 *
	 * @return CranePhar
	 */
	public function resolve($uri);
}