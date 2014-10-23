<?php

namespace WebEdit\Routing\Route;

use Kdyby\Translation;
use Nette\Application;
use Nette\Http;
use Nette\Utils;
use WebEdit\Routing;

final class Collection extends Application\Routers\RouteList
{

    private $translator;
    private $messages = [];
    private $request;

    public function __construct(Translation\Translator $translator, Http\Request $request)
    {
        $this->translator = $translator;
        $this->request = $request;
    }

    public function addRoutes(array $routes)
    {
        foreach ($routes as $mask => $metadata) {
            $this->addRoute($mask, $metadata);
        }
    }

    public function addRoute($mask, $metadata)
    {
        if (is_array($metadata)) {
            foreach ($metadata as $column => &$data) {
                if (!isset($data[Routing\Route::TRANSLATE])) {
                    continue;
                }
                if (!is_array($data)) {
                    $data = [
                        Routing\Route::VALUE => $data,
                    ];
                }
                $data[Routing\Route::WAY_IN] = [$this, 'translateIn' . ucfirst($column)];
                $data[Routing\Route::WAY_OUT] = [$this, 'translateOut' . ucfirst($column)];
                $data[Routing\Route::FILTER_IN] = NULL;
                $data[Routing\Route::FILTER_OUT] = NULL;
            }
        }
        $this[] = new Routing\Route($mask, $metadata);
    }

    public function translateInModule($value, $request)
    {
        $messages = $this->getMessages($request->parameters['locale']);
        $modulesKeys = array_filter(array_keys($messages), function ($key) {
            return strpos($key, '.module') === strlen($key) - strlen('.module');
        });
        $result = [];
        foreach (explode('.', $value) as $part) {
            if (isset($messages[implode('.', array_merge($result, [$part])) . '.module'])) {
                $result[] = $part;
                continue;
            }
            foreach ($modulesKeys as $key) {
                if (Utils\Strings::webalize($messages[$key]) === $part) {
                    $part = explode('.', $key);
                    array_pop($part);
                    $part = end($part);
                    break;
                }
            }
            $result[] = $part;
        }
        return implode(':', array_map(function ($value) {
            return ucfirst($value);
        }, $result));
    }

    public function getMessages($locale)
    {
        if (!isset($this->messages[$locale])) {
            $this->messages[$locale] = $this->prepareValues($this->translator->getMessages($locale), '.');
        }
        return $this->messages[$locale];
    }

    private function prepareValues($values, $separator = '->', $prefix = NULL)
    {
        $data = [];
        foreach ($values as $key => $value) {
            if ($prefix) {
                $key = $prefix . $separator . $key;
            }
            if (is_array($value)) {
                $data += $this->prepareValues($value, $separator, $key);
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }


    public function translateOutModule($value, $request)
    {
        $parts = explode(':', strtolower($value));
        $base = [];
        $result = [];
        foreach ($parts as $part) {
            $base[] = $part;
            $moduleKey = implode('.', $base) . '.module';
            $translated = $this->translator->translate($moduleKey);
            if ($moduleKey === $translated) {
                $moduleKey = $part . '.module';
                $translated = $this->translator->translate($moduleKey);
            }
            if ($translated !== $moduleKey) {
                $result[] = Utils\Strings::webalize($translated);
            } else {
                $result[] = $part;
            }
        }
        return implode('.', $result);
    }

    public function translateIn($value)
    {
        if ($value['action'] === NULL) {
            $value['action'] = 'view';
            return $value;
        }
        $messages = $this->getMessages();
        $actionKey = str_replace(':', '.', strtolower($value['module'] . ':' . $value['presenter'])) . '.action.';
        $actions = array_filter(array_keys($messages), function ($key) use ($actionKey) {
            return strpos($key, $actionKey) !== FALSE;
        });
        foreach ($actions as $key) {
            if (Utils\Strings::webalize($messages[$key]) === $value['action']) {
                $value['action'] = $key;
                break;
            }
        }
        $parts = explode('.', $value['action']);
        $value['action'] = end($parts);
        return $value;
    }

    public function translateOut($value)
    {
        if ($value['action'] === 'view') {
            $value['action'] = NULL;
            return $value;
        }
        $presenter = str_replace(':', '.', strtolower($value['presenter']));
        $key = $presenter . '.action.' . $value['action'];
        $translated = $this->translator->translate($key);
        if ($translated !== $key) {
            $value['action'] = Utils\Strings::webalize($translated);
        } else {
            $value['action'] = $key;
        }
        return $value;
    }

    public function translateInAction($action, $request)
    {
        return $action;
    }

    public function translateOutAction($action, $request)
    {
        return $action;
    }
}
