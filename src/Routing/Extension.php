<?php

namespace WebEdit\Routing;

use WebEdit\Bootstrap;
use WebEdit\Routing\Route;

final class Extension extends Bootstrap\Extension {

    private $defaults = [
        'routes' => []
    ];

    public function beforeCompile() {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);
        $router = $builder->getDefinition('router');
        $routes = [];
        foreach ($this->compiler->getExtensions() as $extension) {
            if (!$extension instanceof Route\Provider) {
                continue;
            }
            $routes += $extension->getRoutingRoutes();
        }
        $routes += $config['routes'];
        foreach ($routes as $mask => $metadata) {
            $router->addSetup('$service[] = new ' . Route::class . '(?, ?)', [$mask, $metadata]);
        }
    }

}
