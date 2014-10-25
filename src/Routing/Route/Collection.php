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
                if (!is_array($data)) {
                    $data = [
                        Routing\Route::VALUE => $data,
                    ];
                }
                $in = Routing\Route::TRANSLATE_IN . ucfirst($column);
                if (method_exists($this, $in)) {
                    $data[Routing\Route::TRANSLATE_IN] = [$this, $in];
                }
                $out = Routing\Route::TRANSLATE_OUT . ucfirst($column);
                if (method_exists($this, $out)) {
                    $data[Routing\Route::TRANSLATE_OUT] = [$this, $out];
                }
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
                if (Utils\Strings::webalize($messages[$key]) === Utils\Strings::webalize($part)) {
                    $part = explode('.', $key);
                    array_pop($part);
                    $part = end($part);
                    break;
                }
            }
            $result[] = $part;
        }
        $module = implode(':', array_map(function ($value) {
            return ucfirst($value);
        }, $result));
        $request->setPresenterName(str_replace($value, $module, $request->getPresenterName()));
        return $module;
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
        $locale = $request->parameters['locale'];
        $parts = explode(':', strtolower($value));
        $base = [];
        $result = [];
        foreach ($parts as $part) {
            $moduleKey = implode('.', array_merge($base, [$part])) . '.module';
            $translated = $this->translator->translate($moduleKey, NULL, [], NULL, $locale);
            if ($moduleKey === $translated) {
                $moduleKey = $part . '.module';
                $translated = $this->translator->translate($moduleKey, NULL, [], NULL, $locale);
            }
            if ($translated !== $moduleKey) {
                $result[] = $this->filterTranslated($translated);
            } else {
                $result[] = $part;
            }
            $base[] = $part;
        }
        return implode('.', $result);
    }

    private function filterTranslated($translated)
    {
        return strtolower(str_replace(' ', '-', trim($translated)));
    }

    public function translateInAction($action, $request)
    {
        $messages = $this->getMessages($request->parameters['locale']);
        $actionKey = str_replace(':', '.', strtolower($request->getPresenterName())) . '.action.';
        $actions = array_filter(array_keys($messages), function ($key) use ($actionKey) {
            return strpos($key, $actionKey) !== FALSE;
        });
        foreach ($actions as $key) {
            if (Utils\Strings::webalize($messages[$key]) === Utils\Strings::webalize($action)) {
                $action = $key;
                break;
            }
        }
        $parts = explode('.', $action);
        $action = end($parts);
        return $action;
    }

    public function translateOutAction($action, $request)
    {
        $presenter = str_replace(':', '.', strtolower($request->getPresenterName()));
        $key = $presenter . '.action.' . $action;
        $translated = $this->translator->translate($key, NULL, [], NULL, $request->parameters['locale']);
        if ($translated !== $key) {
            $action = $this->filterTranslated($translated);
        } else {
            $action = $key;
        }
        return $action;
    }
}
