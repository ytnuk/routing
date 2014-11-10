<?php

namespace WebEdit\Routing\Route;

use Kdyby\Translation;
use Nette\Application;
use Nette\Http;
use Nette\Utils;
use WebEdit\Routing;

/**
 * Class Collection
 *
 * @package WebEdit\Routing
 */
final class Collection extends Application\Routers\RouteList
{

	/**
	 * @var Translation\Translator
	 */
	private $translator;

	/**
	 * @var array
	 */
	private $messages = [];

	/**
	 * @var Http\Request
	 */
	private $request;

	/**
	 * @param Translation\Translator $translator
	 * @param Http\Request $request
	 */
	public function __construct(Translation\Translator $translator, Http\Request $request)
	{
		$this->translator = $translator;
		$this->request = $request;
	}

	/**
	 * @param array $routes
	 */
	public function addRoutes(array $routes)
	{
		foreach ($routes as $mask => $metadata) {
			$this->addRoute($mask, $metadata);
		}
	}

	/**
	 * @param string $mask
	 * @param array|string $metadata
	 */
	public function addRoute($mask, $metadata)
	{
		if (is_array($metadata)) {
			foreach ($metadata as $column => &$data) {
				if ( ! is_array($data)) {
					$data = [Routing\Route::VALUE => $data,];
				}
				$data[Routing\Route::TRANSLATE_IN] = [
					$this,
					'translateIn'
				];
				$out = Routing\Route::TRANSLATE_OUT . ucfirst($column); //TODO: unify translate out methods
				if (method_exists($this, $out)) {
					$data[Routing\Route::TRANSLATE_OUT] = [
						$this,
						$out
					];
				}
			}
		}
		$this[] = new Routing\Route($mask, $metadata);
	}

	/**
	 * @param string $value
	 * @param $request
	 * @param string $type
	 *
	 * @return string
	 */
	public function translateIn($value, $request, $type)
	{
		$messages = $this->getMessages($request->parameters['locale']);
		if ($type === 'module') {
			$presenterKey = '.presenter';
		} else {
			$presenterKey = str_replace(':', '.', strtolower($request->getPresenterName()));
		}
		$messageKeys = array_filter(array_keys($messages), function ($messageKey) use ($presenterKey, $type) {
			return strpos($messageKey, $presenterKey . '.' . $type) !== FALSE;
		});
		$translated = $value;
		foreach ($messageKeys as $messageKey) {
			if (Utils\Strings::webalize($messages[$messageKey]) === Utils\Strings::webalize($value)) {
				$translated = $messageKey;
				break;
			}
		}
		if ($value === $translated) {
			return str_replace('.', ':', $value);
		}
		if ($result = substr($translated, 0, strpos($translated, $presenterKey . '.' . $type))) {
			$parts = explode('.', $result);

			return implode(':', array_map(function ($part) {
				return ucfirst($part);
			}, $parts));
		} else {
			$parts = explode('.', $translated);

			return end($parts);
		}
	}

	/**
	 * @param string $locale
	 *
	 * @return array
	 */
	public function getMessages($locale)
	{
		if ( ! isset($this->messages[$locale])) {
			$this->messages[$locale] = $this->prepareValues($this->translator->getMessages($locale), '.');
		}

		return $this->messages[$locale];
	}

	/**
	 * @param array $values
	 * @param string $separator
	 * @param NULL $prefix
	 *
	 * @return array
	 */
	private function prepareValues(array $values, $separator = '->', $prefix = NULL)
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

	/**
	 * @param string $value
	 * @param $request
	 * @param string $type
	 *
	 * @return string
	 */
	public function translateOutModule($value, $request, $type)
	{
		$key = implode('.', array_merge(explode(':', strtolower($request->getPresenterName())), [
			$type,
			$value
		]));
		$translated = $this->translator->translate($key, NULL, [], NULL, $request->parameters['locale']);
		if ($translated === $key) {
			$key = implode('.', array_merge(explode(':', strtolower($request->getPresenterName())), [
				$type
			]));
			$translated = $this->translator->translate($key, NULL, [], NULL, $request->parameters['locale']);
		}
		$parts = explode('.', $key);

		return $translated === $key ? str_replace(':', '.', end($parts)) : $this->filterTranslated($translated);
	}

	/**
	 * @param string $translated
	 *
	 * @return string
	 */
	private function filterTranslated($translated)
	{
		return strtolower(str_replace(' ', '-', trim($translated)));
	}

	/**
	 * @param string $value
	 * @param $request
	 *
	 * @return string
	 */
	public function translateOutAction($value, $request)
	{
		$key = implode('.', array_merge(explode(':', strtolower($request->getPresenterName())), [
			'action',
			$value
		]));
		$translated = $this->translator->translate($key, NULL, [], NULL, $request->parameters['locale']);

		return $translated === $key ? $key : $this->filterTranslated($translated);
	}
}
