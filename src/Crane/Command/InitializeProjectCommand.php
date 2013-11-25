<?php


namespace Crane\Command;


use Crane\Configuration\ProjectRepository;
use Crane\Configuration\GlobalConfiguration;
use Crane\Configuration\Project;
use Nassau\Silex\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeProjectCommand extends Command
{
	const ARGUMENT_REPOSITORY = 'uri';

	protected function configure()
	{
		return $this->setName('project:init')
			->addArgument(self::ARGUMENT_REPOSITORY, InputArgument::REQUIRED, 'Crane project configuration GIT repository');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$repository = $input->getArgument(self::ARGUMENT_REPOSITORY);
		$output->writeln(sprintf("Fetching crane configuration from: <info>%s</info>", $repository));

		/** @var ProjectRepository $fetcher */
		$fetcher = $this->getApplication()->getService('project-repository');
		$name = $fetcher->getNameFromRepository($repository);
		if (null === $name)
		{
			$output->writeln('<error>Couldn’t find project at target location</error>');
			return 1;
		}
		$output->write(sprintf('Found project: <info>%s</info>… ', $name));
		if ($fetcher->hasProject($name))
		{
			if (false === $fetcher->isProjectFromRepository($name, $repository))
			{
				$output->writeln('<error>conflict, aborting</error>');
				return 1;
			}
			$output->writeln('<comment>updating</comment>');
			$fetcher->updateProject($name);
		}
		else
		{
			$output->writeln('<comment>cloning</comment>');
			$fetcher->saveProject($repository);
		}

		$project = new Project($fetcher->getConfig($name));

		/** @var GlobalConfiguration $globalConfiguration */
		$globalConfiguration = $this->getApplication()->getService('configuration');
		try
		{
			$project = $globalConfiguration->append($project);
			$output->writeln(sprintf('Added project configuration', $project->getName()));
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Project configuration has errors!', 0, $e);
		}

		if (1 !== $project->getTargets()->count())
		{
			$this->chooseTarget($project, $output);
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

	private function chooseTarget(Project $project, OutputInterface $output)
	{
		$output->writeln('Project has multiple target definitions. Do you want to choose one?');

		$targets = ["" => "Skip this for now, decide later"] + array_map(function ($value)
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