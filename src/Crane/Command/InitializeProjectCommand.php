<?php


namespace Crane\Command;


use Crane\Configuration\GlobalConfiguration;
use Crane\Configuration\CraneScheme;
use Crane\Configuration\Project;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeProjectCommand extends Command
{
	const ARGUMENT_URI = 'uri';

	protected function configure()
	{
		return $this->setName('project:init')
			->addArgument(self::ARGUMENT_URI, InputArgument::REQUIRED, 'Crane configuration');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$configurationUri = $input->getArgument(self::ARGUMENT_URI);
		$output->writeln(sprintf("<comment>Fetching crane configuration from: %s</comment>", $configurationUri));

		$configurationUri = CraneScheme::getUri($configurationUri);
		if (null === $configurationUri)
		{
			$output->writeln('<error>Invalid configuration URI: </error>' . $configurationUri);
			return 1;
		}
		$project = json_decode(file_get_contents($configurationUri), true);

		/** @var GlobalConfiguration $globalConfiguration */
		$globalConfiguration = $this->getApplication()->getService('global-configuration');
		try
		{
			$project = $globalConfiguration->append($project);
			$output->writeln(sprintf('<comment>Added project by name: </comment><info>%s</info>', $project->getName()));
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Cannot read configuration from URI: ' . $configurationUri, 0, $e);
		}

		if (1 !== $project->getTargets()->count())
		{
			$this->chooseTarget($project, $input, $output);
		}
		else
		{
			$project->useDefaultTarget();
		}

		if (false === $project->hasCurrentTarget())
		{
			return 0;
		}

		$question = 'Do you want to start the project? [Y/n] ';
		$start = $this->getDialogHelper()->askConfirmation($output, $question);
		if ($start)
		{
			$command = $this->getApplication()->get('project:start');
			$startInput = new ArrayInput([
				StartProjectCommand::ARGUMENT_NAME => $project->getName(),
				StartProjectCommand::ARGUMENT_TARGET => $project->getCurrentTarget(),
				StartProjectCommand::OPTION_RESTART => true
			]);
			return $command->run($startInput, $output);
		}
		return 0;
	}

	private function chooseTarget(Project $project, InputInterface $input, OutputInterface $output)
	{
		$output->writeln('Project has multiple target definitions. Do you want to choose one?');

		$targets = ["" => "<info>Skip this for now, decide later</info>"] + array_map(function ($value)
		{
			if (null === $value)
			{
				return 'run from <comment>localhost</comment>';
			}
			return sprintf('run via ssh from <comment>%s</comment>', $value);
		}, $project->getTargets()->getArrayCopy());
		$value = $this->getDialogHelper()->select($output, "Available options: ", $targets, "");
		if ($value)
		{
			$project->setCurrentTarget($value);
		}
	}

}