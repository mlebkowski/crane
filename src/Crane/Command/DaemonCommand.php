<?php


namespace Crane\Command;


use Crane\Daemon\Daemon;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class DaemonCommand extends Command
{
	const ARGUMENT_DIRECTORY = 'directory';
	const APPLICATION_TAR = 'application/x-tar';
	const APPLICATION_JSON = 'application/json';

	protected function configure()
	{
		return $this->setName('daemon')
			->addArgument(self::ARGUMENT_DIRECTORY, InputArgument::OPTIONAL, 'Working directory', getcwd());
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (php_sapi_name() !== 'cli-server')
		{
			$this->bindServer($output, $input->getArgument(self::ARGUMENT_DIRECTORY));
			return ;
		}

		/** @var \Silex\Application $app */
		$app = $this->getApplication()->getContainer();

		/** @var Daemon $daemon */
		$daemon = $app['daemon'];

		/** @var ConsoleOutputInterface $output */
		$output = $output->getErrorOutput();
		$app->get('{uri}', function (Request $request) use ($output, $daemon)
		{
			$types = $request->getAcceptableContentTypes();
			if (in_array(self::APPLICATION_TAR, $types))
			{
				$type = self::APPLICATION_TAR;
			}
			else
			{
				$type = self::APPLICATION_JSON;
			}

			$uri = trim($request->getRequestUri(), '/');
			$output->write(sprintf('<info>Requesting %s [%s]... </info>', $uri, $type));
			$package = $daemon->getContents($uri, $type);
			if (null === $package)
			{
				$output->writeln('<error>not found</error>');
				return new Response('', 404);
			}

			$output->writeln('<comment>Success</comment>');

			return new Response(file_get_contents($package), 200, [
				"Content-Type" => $type
			]);
		});
		$app->run();
	}

	private function bindServer(OutputInterface $output, $directory)
	{
		$router = $this->getApplication()->getService('path.app') . '/bootstrap.php';
		$cmd = sprintf('php -S 0.0.0.0:%d %s', Daemon::CRANE_PORT, $router);
		$p = new Process($cmd, $directory, null, null, null);
		$p->start(function ($type, $buffer) use ($output)
		{
			$output->write($buffer);
		});
		$p->wait();
	}

}