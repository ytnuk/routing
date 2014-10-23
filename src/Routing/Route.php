<?php

namespace WebEdit\Routing;

use Nette\Application;
use Nette\Http;
use Nette;

final class Route extends Application\Routers\Route
{

    const TRANSLATE = 'translate';
    const WAY_IN = 'in';
    const WAY_OUT = 'out';

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

        if ($params = $this->doFilterParams($this->getRequestParams($appRequest), $appRequest, self::WAY_IN)) {
            return $this->setRequestParams($appRequest, $params);
        }

        return NULL;
    }

    public function constructUrl(Application\Request $appRequest, Http\Url $refUrl)
    {
        if ($params = $this->doFilterParams($this->getRequestParams($appRequest), $appRequest, self::WAY_OUT)) {
            $appRequest = $this->setRequestParams($appRequest, $params);
            return parent::constructUrl($appRequest, $refUrl);
        }

        return NULL;
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


    private function doFilterParams($params, Application\Request $request, $way)
    {
        foreach ($this->filters as $param => $filters) {
            if (!isset($params[$param]) || !isset($filters[$way])) {
                continue;
            }

            if ($way === self::WAY_IN || $params[$param] !== $this->defaults[$param]) {
                $params[$param] = call_user_func($filters[$way], (string)$params[$param], $request);
            }
            if ($params[$param] === NULL) {
                return NULL;
            }
        }

        return $params;
    }


}
