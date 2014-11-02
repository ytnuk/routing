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

	/**
	 * @var bool
	 */
	private $debugMode;

	/**
	 * @param bool $debugMode
	 */
	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		$container->addDefinition('router')
			->setClass(Application\IRouter::class)
			->setFactory(Route\Collection::class)
			->addSetup('addRoutes', [$config['routes']]);
		if ($this->debugMode && $config['debugger']) {
			$container->getDefinition('application')
				->addSetup('@Tracy\Bar::addPanel', [
					new DI\Statement(Bridges\ApplicationTracy\RoutingPanel::class)
				]);
		}
	}
}
