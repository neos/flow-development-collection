<?php
namespace TYPO3\FLOW3\I18n;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A Configuration instance represents settings to be used with the I18n
 * functionality. Examples of such settings are the locale to be used and
 * overrides for message catalogs.
 */
class Configuration {

	/**
	 * @var Locale
	 */
	protected $defaultLocale;

	/**
	 * @var Locale
	 */
	protected $currentLocale;

	/**
	 * @var array
	 */
	protected $fallbackRule = array();

	/**
	 * Constructs a new configuration object with the given locale identifier to
	 * be used as the default locale of this configuration.
	 *
	 * @param string $defaultLocaleIdentifier
	 * @throws Exception\InvalidLocaleIdentifierException
	 */
	public function __construct($defaultLocaleIdentifier) {
		try {
			$this->defaultLocale = new \TYPO3\FLOW3\I18n\Locale($defaultLocaleIdentifier);
		} catch (\TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException $exception) {
			throw new \TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException('The default locale identifier "' . $defaultLocaleIdentifier . '" given is invalid.', 1280935191);
		}
	}

	/**
	 * Returns the default locale of this configuration.
	 *
	 * @return \TYPO3\FLOW3\I18n\Locale
	 */
	public function getDefaultLocale() {
		return $this->defaultLocale;
	}

	/**
	 * Sets the current locale of this configuration.
	 *
	 * @param \TYPO3\FLOW3\I18n\Locale $locale
	 * @return void
	 */
	public function setCurrentLocale(Locale $locale) {
		$this->currentLocale = $locale;
	}

	/**
	 * Returns the current locale. This is the default locale if
	 * no current lcoale has been set or the set current locale has
	 * a language code of "mul".
	 *
	 * @return \TYPO3\FLOW3\I18n\Locale
	 */
	public function getCurrentLocale() {
		if (!$this->currentLocale instanceof \TYPO3\FLOW3\I18n\Locale
			|| $this->currentLocale->getLanguage() === 'mul') {
			return $this->defaultLocale;
		}
		return $this->currentLocale;
	}

	/**
	 * Allows to set a fallback order for locale resolving. If not set,
	 * the implicit inheritance of locales will be used. That is, if a
	 * locale of en_UK is requested, matches will be searched for in en_UK
	 * and en before trying the default locale configured in FLOW3.
	 *
	 * If this is given an order of [dk, za, fr_CA] a request for en_UK will
	 * be looked up in en_UK, en, dk, za, fr_CA, fr before trying the default
	 * locale.
	 *
	 * If strict flag is given in the array, the above example would instead look
	 * in en_UK, dk, za, fr_CA before trying the default locale. In other words,
	 * the implicit fallback is not applied to the locales in the fallback rule.
	 *
	 * Here is an example:
	 *   array('strict' => FALSE, 'order' => array('dk', 'za'))
	 *
	 * @param array $fallbackRule
	 */
	public function setFallbackRule(array $fallbackRule) {
		$this->fallbackRule = $fallbackRule;
	}

	/**
	 * Returns the current fallback rule.
	 *
	 * @return array
	 * @see setFallbackRule()
	 */
	public function getFallbackRule() {
		return $this->fallbackRule;
	}

}

?>