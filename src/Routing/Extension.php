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

    public function getApplicationResources()
    {
        $setup = [];
        foreach (array_reverse($this->resources['routes']) as $mask => $metadata) {
            $setup['addRoute'] = [$mask, $metadata];
        }
        return [
            'services' => [
                [
                    'class' => Routing\Route\Collection::class,
                    'setup' => $setup
                ]
            ]
        ];
    }

}
