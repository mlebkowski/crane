<?php

namespace Crane\Silex;

use Crane\Configuration\ProjectRepository;
use Crane\Configuration\GlobalConfiguration;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ConfigurationServiceProvider implements ServiceProviderInterface
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
		$app['configuration'] = $app->share(function () use ($app)
		{
			return new GlobalConfiguration($app['json-validator']);
		});
		$app['project-repository'] = $app->share(function () use ($app)
		{
			return new ProjectRepository($app['executor.command'], $app['configuration.path']);
		});

		$app['configuration.path'] = getenv('HOME') . '/.crane';
		$app['configuration.path.config'] = $app['configuration.path'] . '/config.json';
	}

	/**
	 * Bootstraps the application.
	 * This method is called after all services are registered
	 * and should be used for "dynamic" configuration (whenever
	 * a service must be requested).
	 */
	public function boot(Application $app)
	{
		$app['configuration'] = $app->share($app->extend('configuration', function (GlobalConfiguration $conf) use ($app)
		{
			$configPath = $app['configuration.path.config'];
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