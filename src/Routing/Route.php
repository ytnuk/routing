<?php

namespace WebEdit\Routing;

use Nette;
use Nette\Application;
use Nette\Http;

final class Route extends Application\Routers\Route
{

    const TRANSLATE = 'translate';
    const TRANSLATE_IN = 'translateIn';
    const TRANSLATE_OUT = 'translateOut';
    const TRANSLATE_PATTERN = '[a-z0-9.áčďéěíňóřšťůúýžÁČĎÉĚÍŇÓŘŠŤŮÚÝŽ-]*';

    public static $styles = [
        '#' => [
            self::PATTERN => '[^/]+',
            self::FILTER_IN => 'rawurldecode',
            self::FILTER_OUT => [__CLASS__, 'param2path'],
        ],
        'module' => [
            self::PATTERN => self::TRANSLATE_PATTERN,
        ],
        'presenter' => [
            self::PATTERN => self::TRANSLATE_PATTERN,
        ],
        'action' => [
            self::PATTERN => self::TRANSLATE_PATTERN,
        ],
    ];

    private $filters = [];

    public function __construct($mask, $metadata = [], $flags = 0)
    {
        $this->filters = $metadata;
        parent::__construct($mask, $metadata, $flags);
    }

    public function match(Http\IRequest $httpRequest)
    {
        $appRequest = parent::match($httpRequest);
        if (!$appRequest) {
            return $appRequest;
        }

        if ($params = $this->doFilterParams($this->getRequestParams($appRequest), $appRequest, self::TRANSLATE_IN)) {
            return $this->setRequestParams($appRequest, $params);
        }

        return NULL;
    }

    private function doFilterParams($params, Application\Request $request, $way)
    {
        foreach ($this->filters as $param => $filters) {
            if (!isset($params[$param]) || !isset($filters[$way])) {
                continue;
            }

            if ($way === self::TRANSLATE_IN || $params[$param] !== $this->defaults[$param]) {
                $params[$param] = call_user_func($filters[$way], (string)$params[$param], $request);
            }
            if ($params[$param] === NULL) {
                return NULL;
            }
        }

        return $params;
    }

    private function getRequestParams(Application\Request $appRequest)
    {
        $params = $appRequest->getParameters();
        $metadata = $this->getDefaults();

        $presenter = $appRequest->getPresenterName();
        $params[self::PRESENTER_KEY] = $presenter;

        if (isset($metadata[self::MODULE_KEY])) { // try split into module and [submodule:]presenter parts
            $module = $metadata[self::MODULE_KEY];
            if (isset($module['fixity']) && strncasecmp($presenter, $module[self::VALUE] . ':', strlen($module[self::VALUE]) + 1) === 0) {
                $a = strlen($module[self::VALUE]);
            } else {
                $a = strrpos($presenter, ':');
            }

            if ($a === FALSE) {
                $params[self::MODULE_KEY] = '';
            } else {
                $params[self::MODULE_KEY] = substr($presenter, 0, $a);
                $params[self::PRESENTER_KEY] = substr($presenter, $a + 1);
            }
        }

        return $params;
    }


    private function setRequestParams(Application\Request $appRequest, array $params)
    {
        $metadata = $this->getDefaults();

        if (!isset($params[self::PRESENTER_KEY])) {
            throw new \InvalidStateException('Missing presenter in route definition.');
        }
        if (isset($metadata[self::MODULE_KEY])) {
            if (!isset($params[self::MODULE_KEY])) {
                throw new \InvalidStateException('Missing module in route definition.');
            }
            $presenter = $params[self::MODULE_KEY] . ':' . $params[self::PRESENTER_KEY];
            unset($params[self::MODULE_KEY], $params[self::PRESENTER_KEY]);

        } else {
            $presenter = $params[self::PRESENTER_KEY];
            unset($params[self::PRESENTER_KEY]);
        }

        $appRequest->setPresenterName($presenter);
        $appRequest->setParameters($params);

        return $appRequest;
    }

    public function constructUrl(Application\Request $appRequest, Http\Url $refUrl)
    {
        if ($params = $this->doFilterParams($this->getRequestParams($appRequest), $appRequest, self::TRANSLATE_OUT)) {
            $appRequest = $this->setRequestParams($appRequest, $params);
            return parent::constructUrl($appRequest, $refUrl);
        }

        return NULL;
    }


}
