<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cilex\Provider\Console\Adapter\Silex\ConsoleServiceProvider;
use Cilex\Provider\Console\ContainerAwareApplication;
use Symfony\Component\Finder\Finder;

$silex = new Silex\Application([
	'path.app'    => __DIR__,
	'path.config' => __DIR__ . '/config',
	'path.src'    => realpath(__DIR__ . '/../src'),
	'path.images' => realpath(__DIR__ . '/../images'),
]);

foreach(Finder::create()->in($silex['path.config'])->name('*.yaml') as $file)
{
	$silex->register(new \Igorw\Silex\ConfigServiceProvider((string) $file));
};

$silex->register(new ConsoleServiceProvider, [
	'console.name' => 'Docplanner Crane',
	'console.version' => '1.0.0',
]);

$silex->register(new \Nassau\Silex\Provider\CommandsProvider, [
	'commands.path' => $silex['path.src'],
]);

$silex->register(new \Crane\Silex\DockerServiceProvider);
$silex->register(new \Crane\Silex\DaemonProvider);
$silex->register(new \Crane\Silex\JsonValidatorProvider);
$silex->register(new \Crane\Silex\ResolverServiceProvider);

// since starting a console does not boot Silex
$silex->boot();
/** @var ContainerAwareApplication $console */
$console = $silex->offsetGet('console');

if (php_sapi_name() === 'cli-server')
{
	$input = new \Symfony\Component\Console\Input\StringInput('daemon');
	$console->run($input);
}
else
{
	$console->run();
}
