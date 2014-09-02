<?php

namespace WebEdit\Routing;

use WebEdit\Bootstrap;
use WebEdit\Routing;

final class Extension extends Bootstrap\Extension implements Routing\Provider {

    public function beforeCompile() {
        $this->setupRoutes();
    }

    private function setupRoutes() {
        $builder = $this->getContainerBuilder();
        $router = $builder->getDefinition('router');
        foreach (array_reverse($this->resources['routes']) as $mask => $metadata) {
            $router->addSetup('$service[] = new ' . Routing\Route::class . '(?, ?)', [$mask, $metadata]);
        }
    }

    public function getRoutingResources() {
        return [
            'routes' => []
        ];
    }

}
