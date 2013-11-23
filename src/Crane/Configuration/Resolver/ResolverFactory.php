<?php


namespace Crane\Configuration\Resolver;

use Crane\Configuration\CranePhar;

class ResolverFactory implements ResolverInterface
{

	/**
	 * @var ResolverInterface[]
	 */
	private $resolvers;

	/**
	 * @param ResolverInterface $resolver
	 *
	 * @return $this
	 */
	public function add(ResolverInterface $resolver)
	{
		$this->resolvers[] = $resolver;
		return $this;
	}

	/**
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function isHandling($uri)
	{
		return array_reduce($this->resolvers, function ($start, ResolverInterface $resolver) use ($uri)
		{
			return $start || $resolver->isHandling($uri);
		});
	}

	/**
	 * @param string $uri
	 *
	 * @throws \RuntimeException
	 * @return CranePhar
	 */
	public function resolve($uri)
	{
		foreach ($this->resolvers as $resolver)
		{
			if ($resolver->isHandling($uri))
			{
				return $resolver->resolve($uri);
			}
		}
		throw new \RuntimeException('Cannot resolve: ' . $uri);
	}
}