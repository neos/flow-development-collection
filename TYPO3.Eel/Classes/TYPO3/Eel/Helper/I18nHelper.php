<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\Helper\I18n\TranslationParameterToken;
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * I18n helper for Eel - provides methods to translate labels using XLIFF
 * translations stored in Flow Packages
 *
 * There are three ways of usage:
 * * By calling translate, you can pass all necessary parameters into one method and get back the translated string
 * * By calling translate with a translation shorthand string (PackageKey:Source:trans-unit-id), this shorthat will be
 *   translated directly
 * * id and value will return a token object, that'll help you collect those parameters without the need to provide
 *   all of them and without the need to provide them in order
 *
 * = Examples =
 *
 * <code id="Calling translate">
 * ${I18n.translate('my-trans-unit-id', 'myOriginalLabel', ['an argument'], 'Main', 'MyAwesome.Package', 42, 'en_US')}
 * </code>
 * <output>
 * The translated string or my-trans-unit-id, if no translation could be found.
 * </output>
 *
 * <code id="Calling translate with a shorthand string">
 * ${I18n.translate('MyAwesome.Package:Main:my-trans-unit-id')}
 * </code>
 * <output>
 * The translated string or my-trans-unit-id, if no translation could be found.
 * </output>
 *
 * <code id="Calling id">
 * ${I18n.id('my-trans-unit-id').arguments(['an argument']).package('MyAwesome.Package')}
 * </code>
 * <output>
 * The translated string or my-trans-unit-id, if no translation could be found.
 * </output>
 */
class I18nHelper implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Eel\Helper\I18n\TranslationParameterTokenFactory
	 */
	protected $translationParameterTokenFactory;

	/**
	 * Get the translated value for an id or original label
	 *
	 * If only id is set and contains a translation shorthand string, translate
	 * according to that shorthand
	 *
	 * In all other cases:
	 *
	 * Replace all placeholders with corresponding values if they exist in the
	 * translated label.
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @param array $arguments Numerically indexed array of values to be inserted into placeholders
	 * @param string $source Name of file with translations
	 * @param string $package Target package key. If not set, the current package key will be used
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param string $locale An identifier of locale to use (NULL for use the default locale)
	 * @return string Translated label or source label / ID key
	 * @throws \TYPO3\Eel\Exception
	 */
	public function translate($id, $value = NULL, $arguments = array(), $source = 'Main', $package = NULL, $quantity = NULL, $locale = NULL) {
		if (
			$value == NULL &&
			$arguments == array() &&
			$source == 'Main' &&
			$package == NULL &&
			$quantity == NULL &&
			$locale == NULL &&
			substr_count($id, ':') === 2
		) {
			return $this->translateByShortHandString($id);
		}

		return $this->translateByExplicitlyPassedOrderedArguments($id, $value, $arguments, $source, $package, $quantity, $locale);
	}

	/**
	 * Get the translated value for an id or original label
	 *
	 * Replace all placeholders with corresponding values if they exist in the
	 * translated label.
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @param array $arguments Numerically indexed array of values to be inserted into placeholders
	 * @param string $source Name of file with translations
	 * @param string $package Target package key. If not set, the current package key will be used
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @param string $locale An identifier of locale to use (NULL for use the default locale)
	 * @return string Translated label or source label / ID key
	 * @throws \TYPO3\Eel\Exception
	 */
	protected function translateByExplicitlyPassedOrderedArguments($id, $value = NULL, $arguments = array(), $source = 'Main', $package = NULL, $quantity = NULL, $locale = NULL) {
		$translationParameterToken = $this->translationParameterTokenFactory->create();
		$translationParameterToken
			->id($id)
			->value($value)
			->arguments($arguments)
			->source($source)
			->package($package)
			->quantity($quantity)
			->locale($locale);

		return $translationParameterToken->translate();
	}

	/**
	 * Translate by shorthand string
	 *
	 * @param string $shortHandString (PackageKey:Source:trans-unit-id)
	 * @return string Translated label or source label / ID key
	 * @throws \InvalidArgumentException
	 */
	protected function translateByShortHandString($shortHandString) {
		$shortHandStringParts = explode(':', $shortHandString);
		if (count($shortHandStringParts) === 3) {
			list($package, $source, $id) = $shortHandStringParts;

			return $this->translationParameterTokenFactory->createWithId($id)
				->package($package)
				->source(str_replace('.', '/', $source))
				->translate();
		}

		throw new \InvalidArgumentException(sprintf('The translation shorthand string "%s" has the wrong format', $shortHandString), 1436865829);
	}

	/**
	 * Start collection of parameters for translation by id
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function id($id) {
		return $this->translationParameterTokenFactory->createWithId($id);
	}

	/**
	 * Start collection of parameters for translation by original label
	 *
	 * @param string $value
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function value($value) {
		return $this->translationParameterTokenFactory->createWithValue($value);
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
