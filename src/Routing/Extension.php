<?php

namespace WebEdit\Routing;

use WebEdit\Bootstrap;
use WebEdit\Routing;

final class Extension extends Bootstrap\Extension {

    private $resources = [
        'routes' => [
            '[<locale [a-z]{2}(_[A-Z]{2})?>/]<module [a-z.]+>[/<action [a-z]+>][/<id [0-9]+>]' => [
                'module' => 'Home:Front',
                'presenter' => 'Presenter',
                'action' => 'view'
            ]
        ]
    ];

    public function beforeCompile() {
        $this->loadResources();
        $this->setupRoutes();
    }

    private function loadResources() {
        $this->resources = $this->getConfig($this->resources);
        foreach ($this->compiler->getExtensions() as $extension) {
            if (!$extension instanceof Routing\Provider) {
                continue;
            }
            $this->resources = array_merge_recursive($this->resources, $extension->getRoutingResources());
        }
    }

    private function setupRoutes() {
        $builder = $this->getContainerBuilder();
        $router = $builder->getDefinition('router');
        foreach (array_reverse($this->resources['routes']) as $mask => $metadata) {
            $router->addSetup('$service[] = new ' . Routing\Route::class . '(?, ?)', [$mask, $metadata]);
        }
    }

}
