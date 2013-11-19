<?php


namespace Crane\Docker;


use Crane\Docker\Executor\CommandExecutor;
use Crane\Docker\Executor\ExecutorFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DockerServiceProvider implements ServiceProviderInterface
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
		$app['executor.factory'] = $app->share(function ()
		{
			return new ExecutorFactory;
		});
		$app['executor.command'] = function ()
		{
			return new CommandExecutor;
		};
		$app['docker'] = $app->share(function () use ($app)
		{
			return new Docker($app);
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