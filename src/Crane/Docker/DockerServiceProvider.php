<?php


namespace Crane\Docker;


use Crane\Docker\Executor\CommandExecutor;
use Crane\Docker\Executor\ExecutorFactory;
use Crane\Docker\Image\Image;
use Crane\Docker\Image\ImageCollection;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

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

		$app['images'] = $app->share(function () use ($app)
		{
			$collection = new ImageCollection;
			$main = $app['Docker']['Main'];
			$collection->setNamespace($app['Docker']['User']);
			foreach ($app['Docker']['Images'] as $name => $settings)
			{
				if (null === $settings)
				{
					$image = (new Image($name))->setRunnable(false);
				}
				else
				{
					$settings = new ParameterBag((array) $settings);
					/** @var Image $image */
					$image = (new Image($name))
						->setMain($main === $name)
						->setPorts($settings->get('ports'))
						->setRequiredImages($settings->get('require'))
						->setVolumes($settings->get('volumes'))
						->setHostname($settings->get('hostname'))
						->setUseTTY($settings->get('useTTY'));
				}
				$collection->offsetSet($name, $image);
			}
			return $collection;
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