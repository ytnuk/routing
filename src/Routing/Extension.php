<?php

namespace WebEdit\Routing;

use WebEdit\Application;
use WebEdit\Module;
use WebEdit\Routing;

final class Extension extends Module\Extension implements Application\Provider
{

    protected $resources = [
        'routes' => []
    ];

    public function beforeCompile()
    {
        $collection = $this->getContainerBuilder()->getDefinition($this->prefix('route.collection'));
        foreach (array_reverse($this->resources['routes']) as $mask => $metadata) {
            $collection->addSetup('addRoute', [$mask, $metadata]);
        }
    }

    public function getApplicationResources()
    {
        return [
            'services' => [
                $this->prefix('route.collection') => Routing\Route\Collection::class
            ]
        ];
    }

}
