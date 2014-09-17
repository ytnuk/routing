<?php

namespace WebEdit\Routing\Route;

use Nette\Application;
use WebEdit\Routing;

final class Collection extends Application\Routers\RouteList
{

    public function addRoutes(array $routes)
    {
        foreach ($routes as $mask => $metadata) {
            $this->addRoute($mask, $metadata);
        }
    }

    public function addRoute($mask, $metadata)
    {
        $this[] = new Routing\Route($mask, $metadata);
    }

}
