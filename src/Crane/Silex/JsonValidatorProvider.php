<?php


namespace Crane\Silex;


use Crane\Validator\CraneJsonValidator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JsonValidatorProvider implements ServiceProviderInterface
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
		$app['json-validator'] = $app->share(function () use ($app)
		{
			return new CraneJsonValidator($app['path.app'] . '/crane-schema.json');
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