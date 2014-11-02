<?php

namespace WebEdit\Routing;

use Nette\DI;

/**
 * Class Extension
 *
 * @package WebEdit\Routing
 */
final class Extension extends DI\CompilerExtension
{

	private $defaults = [
		'routes' => []
	];

	public function beforeCompile()
	{
		$config = $this->getConfig($this->defaults);
		$this->getContainerBuilder()
			->getDefinition('router')
			->setFactory(Route\Collection::class)
			->addSetup('addRoutes', [$config['routes']]);
	}
}
