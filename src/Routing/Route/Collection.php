<?php

namespace WebEdit\Routing\Route;

use Nette\Application;
use WebEdit\Routing;

final class Collection extends Application\Routers\RouteList
{

    public function addRoute($mask, $metadata)
    {
        $this[] = new Routing\Route($mask, $metadata);
    }

}
