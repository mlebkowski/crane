<?php

namespace Crane\Silex;

use Crane\Configuration\GlobalConfiguration;
use Crane\Configuration\Resolver\FilesystemResolver;
use Crane\Configuration\Resolver\HttpResolver;
use Crane\Configuration\Resolver\ResolverFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ResolverServiceProvider implements ServiceProviderInterface
{

	/**
	 * Registers services on the given app.
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Application $app An Application instance
	 */
	public function register(Application $app)
	{
		$app['global-configuration'] = $app->share(function () use ($app)
		{
			return new GlobalConfiguration($app['json-validator']);
		});
		$app['resolver.factory'] = $app->share(function () use ($app)
		{
			return (new ResolverFactory)
				->add(new FilesystemResolver)
				->add(new HttpResolver);
		});
	}

	/**
	 * Bootstraps the application.
	 * This method is called after all services are registered
	 * and should be used for "dynamic" configuration (whenever
	 * a service must be requested).
	 */
	public function boot(Application $app)
	{
		$app['global-configuration'] = $app->share($app->extend('global-configuration', function (GlobalConfiguration $conf)
		{
			$configPath = getenv('HOME') . '/.crane/config.json';
			if (file_exists($configPath))
			{
				$data = (array) json_decode(file_get_contents($configPath), true);
				foreach ($data as $item)
				{
					$conf->append($item);
				}
			}
			register_shutdown_function(function () use ($configPath, $conf) {
				if (0 !== sizeof($conf))
				{
					if (false === is_dir(dirname($configPath)))
					{
						mkdir(dirname($configPath), 0700, true);
					}
					file_put_contents($configPath, json_encode($conf, JSON_PRETTY_PRINT));
				}
				elseif (file_exists($configPath))
				{
					unlink($configPath);
				}
			});

			return $conf;
		}));

	}
}