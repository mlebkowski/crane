<?php


namespace Crane\Configuration\Resolver;


use Crane\Configuration\CranePhar;

class FilesystemResolver implements ResolverInterface
{

	/**
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function isHandling($uri)
	{
		$scheme = parse_url($uri, PHP_URL_SCHEME);
		return in_array($scheme, [null, 'file']) && file_exists($uri);
	}

	/**
	 * @param string $uri
	 *
	 * @return CranePhar
	 */
	public function resolve($uri)
	{
		$path = dirname(realpath($uri));
		$json = json_decode(file_get_contents($uri));

		$name = $path . '/' . $json['name'] . '.tar';
		$phar = new CranePhar($name);
		$phar->buildFromDirectory($path, '/(?<!\.tar)$/i');
		return $phar;
	}
}