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

	public function beforeCompile()
	{
		$config = $this->getConfig($this->defaults);
		if ($config['debugger']) {
			$this->getContainerBuilder()
				->getDefinition('application')
				->addSetup('@Tracy\Bar::addPanel', [
					new DI\Statement(Bridges\ApplicationTracy\RoutingPanel::class)
				]);
		}
	}

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$this->getContainerBuilder()
			->addDefinition('router')
			->setClass('Nette\Application\IRouter')
			->setFactory(Route\Collection::class)
			->addSetup('addRoutes', [$config['routes']]);
	}
}
