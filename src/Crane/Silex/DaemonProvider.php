<?php


namespace Crane\Silex;


use Crane\Daemon\Daemon;
use Crane\Daemon\Packager\DirectoryPackager;
use Crane\Daemon\Packager\PackagerFactory;
use Crane\Daemon\Packager\PharPackager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DaemonProvider implements ServiceProviderInterface
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
		$app['daemon'] = $app->share(function () use ($app)
		{
			return new Daemon(getcwd(), $app['packager.factory']);
		});
		$app['packager.factory'] = $app->share(function ()
		{
			return (new PackagerFactory)
				->add(new PharPackager)
				->add(new DirectoryPackager);
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
		// noop
	}
}