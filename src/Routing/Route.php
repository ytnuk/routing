<?php

namespace WebEdit\Routing;

use Nette\Application;

final class Route extends Application\Routers\Route
{

    const TRANSLATE = 'translate';
    public static $styles = array(
        '#' => array( // default style for path parameters
            self::PATTERN => '[^/]+',
            self::FILTER_IN => 'rawurldecode',
            self::FILTER_OUT => array(__CLASS__, 'param2path'),
        ),
        '?#' => array( // default style for query parameters
        ),
        'module' => array(
            self::PATTERN => '[a-z][a-z0-9.-]*',
            self::FILTER_IN => null,
            self::FILTER_OUT => null
        ),
        'presenter' => array(
            self::PATTERN => '[a-z][a-z0-9.-]*',
            self::FILTER_IN => array(__CLASS__, 'path2presenter'),
            self::FILTER_OUT => array(__CLASS__, 'presenter2path'),
        ),
        'action' => array(
            self::PATTERN => '[a-z][a-z0-9.-]*',
            self::FILTER_IN => null,
            self::FILTER_OUT => null,
        ),
        '?module' => array(),
        '?presenter' => array(),
        '?action' => array(),
    );

}
