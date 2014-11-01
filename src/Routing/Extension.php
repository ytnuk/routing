<?php

namespace WebEdit\Routing;

use Nette\DI;
use WebEdit\Config;
use WebEdit\Routing;

/**
 * Class Extension
 *
 * @package WebEdit\Routing
 */
final class Extension extends DI\CompilerExtension implements Config\Provider
{

	/**
	 * @var array
	 */
	private $defaults = [
		'routes' => []
	];

	/**
	 * @return array
	 */
	public function getConfigResources()
	{
		return [
			'services' => [
				'router' => Routing\Route\Collection::class,
			]
		];
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);
		$builder->getDefinition('router')
			->addSetup('addRoutes', [$config['routes']]);
	}
}
