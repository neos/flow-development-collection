<?php
namespace TYPO3\Eel\Helper\I18n;

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
use TYPO3\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Eel\Exception;
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Provides a chainable interface to collect all arguments needed to
 * translate messages using source message or key ID
 *
 * It also translates labels according to the configuration it stores
 */
class TranslationParameterToken implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var Translator
	 */
	protected $translator;

	/**
	 * Key/Value store to keep the collected parameters
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Set the id.
	 *
	 * @param string $id Id to use for finding translation (trans-unit id in XLIFF)
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function id($id) {
		$this->parameters['id'] = $id;
		return $this;
	}

	/**
	 * Set the value.
	 *
	 * @param string $value If $key is not specified or could not be resolved, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function value($value) {
		$this->parameters['value'] = $value;
		return $this;
	}

	/**
	 * Set the arguments.
	 *
	 * @param array $arguments Numerically indexed array of values to be inserted into placeholders
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function arguments(array $arguments) {
		$this->parameters['arguments'] = $arguments;
		return $this;
	}

	/**
	 * Set the source.
	 *
	 * @param string $source Name of file with translations
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function source($source) {
		$this->parameters['source'] = $source;
		return $this;
	}

	/**
	 * Set the package.
	 *
	 * @param string $package Target package key. If not set, the current package key will be used
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function package($package) {
		$this->parameters['package'] = $package;
		return $this;
	}

	/**
	 * Set the quantity.
	 *
	 * @param mixed $quantity A number to find plural form for (float or int), NULL to not use plural forms
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 */
	public function quantity($quantity) {
		$this->parameters['quantity'] = $quantity;
		return $this;
	}

	/**
	 * Set the locale. The locale Identifier will be converted into
	 * a \TYPO3\Flow\I18n\Locale
	 *
	 * @param string $locale An identifier of locale to use (NULL for use the default locale)
	 * @return \TYPO3\Eel\Helper\I18n\TranslationParameterToken
	 * @throws \TYPO3\Eel\Exception
	 */
	public function locale($locale) {
		try {
			$this->parameters['locale'] = new Locale($locale);
		} catch (InvalidLocaleIdentifierException $e) {
			throw new Exception(sprintf('"%s" is not a valid locale identifier.', $locale), 1436784806);
		}

		return $this;
	}

	/**
	 * Translate according to currently collected parameters
	 *
	 * @param $overrides An associative array to override the collected parameters
	 * @return string
	 */
	public function translate(array $overrides = array()) {
		array_replace_recursive($this->parameters, $overrides);

		$id = isset($this->parameters['id']) ? $this->parameters['id'] : NULL;
		$value = isset($this->parameters['value']) ? $this->parameters['value'] : NULL;
		$arguments = isset($this->parameters['arguments']) ? $this->parameters['arguments'] : array();
		$source = isset($this->parameters['source']) ? $this->parameters['source'] : 'Main';
		$package = isset($this->parameters['package']) ? $this->parameters['package'] : NULL;
		$quantity = isset($this->parameters['quantity']) ? $this->parameters['quantity'] : NULL;
		$locale = isset($this->parameters['locale']) ? $this->parameters['locale'] : NULL;

		if ($id === NULL) {
			return $this->translator->translateByOriginalLabel($value, $arguments, $quantity, $locale, $source, $package);
		}

		$translation = $this->translator->translateById($id, $arguments, $quantity, $locale, $source, $package);
		if ($translation === $id) {
			if ($value) {
				return $this->translator->translateByOriginalLabel($value, $arguments, $quantity, $locale, $source, $package);
			}
		}

		return $translation;
	}

	/**
	 * Runs translate to avoid the need of calling translate as a finishing method
	 */
	public function __toString() {
		return $this->translate();
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
