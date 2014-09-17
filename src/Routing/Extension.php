<?php

namespace WebEdit\Routing;

use WebEdit\Application;
use WebEdit\Module;
use WebEdit\Routing;

final class Extension extends Module\Extension implements Application\Provider
{

    public function getResources()
    {
        return [
            'routes' => []
        ];
    }

    public function getApplicationResources()
    {
        return [
            'services' => [
                [
                    'class' => Routing\Route\Collection::class,
                    'setup' => [
                        'addRoutes' => [$this['routes']]
                    ]
                ]
            ]
        ];
    }

}
