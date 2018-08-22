<?php
namespace Neos\Flow\I18n;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;

class LocaleRoutePart extends DynamicRoutePart
{
	/**
	 * The split string represents the end of a Dynamic Route Part.
	 * If it is empty, Route Part will be equal to the remaining request path.
	 *
	 * @var string
	 */
	protected $splitString = '';

	/**
	 * @var \Neos\Flow\I18n\Service
	 * @Flow\Inject
	 */
	protected $i18nService;

	protected function initializeObject()
	{
		$this->defaultValue = (string)$this->i18nService->getConfiguration()->getDefaultLocale();
	}

	/**
	 * Checks, whether given value can be matched.
	 * In the case of default Dynamic Route Parts a value matches when it's not empty.
	 * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 */
	protected function matchValue($value)
	{
		if ($value === null || $value === '') {
			return false;
		}

		try {
			$value = rawurldecode($value);
			$this->value = (string)new Locale($value);

			return true;
		} catch (Exception\InvalidLocaleIdentifierException $exception) {
		}

		return false;
	}

	/**
	 * Checks, whether given value can be resolved and if so, sets $this->value to the resolved value.
	 * If $value is empty, this method checks whether a default value exists.
	 * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
	 *
	 * @param mixed $value value to resolve
	 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
	 */
	protected function resolveValue($value)
	{
		if ($value === null) {
			return false;
		}
		if ($value instanceof Locale) {
			$value = (string)$value;
		}
		$this->value = rawurlencode($value);
		if ($this->lowerCase) {
			$this->value = strtolower($this->value);
		}
		return true;
	}
}
