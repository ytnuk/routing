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
    private $messages;
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
                $data[Routing\Route::FILTER_IN] = [$this, 'translateIn' . ucfirst($column)];
                $data[Routing\Route::FILTER_OUT] = [$this, 'translateOut' . ucfirst($column)];
            }
        }
        $this[] = new Routing\Route($mask, $metadata);
    }

    public function translateInLocale($value)
    {
        $this->translator->setLocale($value);
        return $value;
    }

    public function translateOutLocale($value)
    {
        return $value;
    }

    public function translateInModule($value)
    {
        $moduleKey = '.module'; //TODO: match at end of string
        $messages = $this->getMessages();
        $modules = array_filter(array_keys($messages), function ($key) use ($moduleKey) {
            return strpos($key, $moduleKey) !== FALSE;
        });
        $moduleKey = $value;
        foreach ($modules as $key) {
            if (Utils\Strings::webalize($messages[$key]) === $value) {
                $moduleKey = $key;
                break;
            }
        }
        $module = explode('.', $moduleKey);
        if ($moduleKey !== $value) {
            array_pop($module);
        }
        $module = array_map(function ($value) {
            return ucfirst($value);
        }, $module);
        return implode(':', $module);
    }

    public function getMessages()
    {
        if (!$this->messages) {
            $this->messages = $this->prepareValues($this->translator->getMessages(), '.');
        }
        return $this->messages;
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

    public function translateOutModule($value)
    {
        $value = str_replace(':', '.', strtolower($value));
        $moduleKey = $value . '.module';
        $translated = $this->translator->translate($moduleKey);
        if ($translated !== $moduleKey) {
            return Utils\Strings::webalize($translated);
        } else {
            return $value;
        }
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
}
