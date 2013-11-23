<?php

namespace Nassau\Silex;

use Cilex\Provider\Console\ContainerAwareApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Helper\DialogHelper;

abstract class Command extends ConsoleCommand
{
	/**
	 * @return ContainerAwareApplication
	 */
	public function getApplication()
	{
		return parent::getApplication();
	}

	/**
	 * @return DialogHelper
	 */
	public function getDialogHelper()
	{
		return $this->getHelper('dialog');
	}

}