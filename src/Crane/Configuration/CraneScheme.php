<?php

namespace Crane\Configuration;

use Crane\Daemon\Daemon;

class CraneScheme
{

	public static function getUri($uri)
	{
		$scheme = parse_url($uri, PHP_URL_SCHEME);
		if ('file' === $scheme || null === $scheme)
		{
			return realpath($uri);
		}

		$uri = parse_url($uri);

		$port = $uri['scheme'] == 'crane' ? Daemon::CRANE_PORT : 80;

		return vsprintf("http://%s:%d/%s%s", [
			$uri['host'],
			isset($uri['port']) ? $uri['port'] : $port,
			isset($uri['path']) ? ltrim($uri['path'], '/') : "",
			isset($uri['query']) ? "?" . $uri['query'] : "",
		]);
	}

}