<?php

namespace Nassau\Silex\Provider;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Application as Console;

class CommandsProvider implements ServiceProviderInterface
{

	/**
	 * Registers services on the given app.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Application $app An Application instance
	 */
	public function register(Application $app)
	{
		// :)
	}

	/**
	 * Bootstraps the application.
	 *
	 * This method is called after all services are registered
	 * and should be used for "dynamic" configuration (whenever
	 * a service must be requested).
	 */
	public function boot(Application $app)
	{
		$src = $app->offsetExists('commands.path') ? $app['commands.path'] : $app['path.src'];

		$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src));

		$commands = new \ArrayObject;

		/** @var RecursiveDirectoryIterator $dir */
		for ($dir->rewind(); $dir->valid(); $dir->next())
		{
			if ($dir->isDot())
			{
				continue;
			}
			if ('php' !== $dir->getExtension())
			{
				continue;
			}

			$name = $dir->getSubPathname();
			$name = substr($name, 0, -4);
			$className = str_replace(DIRECTORY_SEPARATOR, '\\', $name);

			$r = new ReflectionClass($className);
			if ($r->isAbstract() || false === $r->isSubclassOf("Symfony\\Component\\Console\\Command\\Command"))
			{
				continue;
			}

			$commands->append(new $className);
		}

		/** @noinspection PhpParamsInspection */
		$app['console'] = $app->share($app->extend('console', function (Console $console) use ($commands)
		{
			foreach ($commands as $command)
			{
				$console->add($command);
			}
			return $console;
		}));
	}
}