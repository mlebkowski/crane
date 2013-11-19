<?php

namespace Nassau\Silex;

use Cilex\Provider\Console\ContainerAwareApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

abstract class Command extends ConsoleCommand
{
	/**
	 * @return ContainerAwareApplication
	 */
	public function getApplication()
	{
		return parent::getApplication();
	}

}