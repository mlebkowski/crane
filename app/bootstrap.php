<?php

$fileName = __DIR__ . '/../vendor/autoload.php';
if (false === file_exists($fileName))
{
	$fileName = __DIR__ . '/../../../autoload.php';
}
/** @noinspection PhpIncludeInspection */
require_once $fileName;

use Cilex\Provider\Console\Adapter\Silex\ConsoleServiceProvider;
use Cilex\Provider\Console\ContainerAwareApplication;
use Symfony\Component\Finder\Finder;

$silex = new Silex\Application([
	'path.app'       => __DIR__,
	'path.config'    => __DIR__ . '/config',
	'path.resources' => __DIR__ . '/resources',
	'path.src'       => realpath(__DIR__ . '/../src'),
	'path.images'    => realpath(__DIR__ . '/../images'),
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
$silex->register(new \Crane\Silex\JsonValidatorProvider);
$silex->register(new \Crane\Silex\ConfigurationServiceProvider);

// since starting a console does not boot Silex
$silex->boot();
/** @var ContainerAwareApplication $console */
$console = $silex->offsetGet('console');
$console->run();
