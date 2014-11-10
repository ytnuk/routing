<?php

namespace WebEdit\Routing;

use Nette\Application;
use Nette\Bridges;
use Nette\DI;

/**
 * Class Extension
 *
 * @package WebEdit\Routing
 */
final class Extension extends Bridges\ApplicationDI\RoutingExtension
{

	public function loadConfiguration()
	{
		parent::loadConfiguration();
		$config = $this->getConfig($this->defaults);
		$this->getContainerBuilder()
			->removeDefinition('router');
		$this->getContainerBuilder()
			->addDefinition('router')
			->setClass('Nette\Application\IRouter')
			->setFactory(Route\Collection::class)
			->addSetup('addRoutes', [$config['routes']]);
	}
}
