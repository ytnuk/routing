<?php

namespace WebEdit\Routing;

use WebEdit\Application;
use WebEdit\Module;
use WebEdit\Routing;

/**
 * Class Extension
 *
 * @package WebEdit\Routing
 */
final class Extension extends Module\Extension implements Application\Provider
{

	/**
	 * @return array
	 */
	public function getResources()
	{
		return ['routes' => []];
	}

	/**
	 * @return array
	 */
	public function getApplicationResources()
	{
		return ['services' => [['class' => Routing\Route\Collection::class, 'setup' => ['addRoutes' => [$this['routes']]]]]];
	}
}
