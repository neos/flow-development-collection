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
 * Represents a locale
 *
 * Objects of this kind conveniently represent locales usually described by
 * locale identifiers such as de_DE, en_Latin_US etc. The locale identifiers
 * used are defined in the Unicode Technical Standard #35 (Unicode Locale
 * Data Markup Language).
 *
 * Using this class asserts the validity of the used locale and provides you
 * with some useful methods for getting more information about it.
 *
 * Please note that this class represents locale identifier with valid syntax,
 * but it does not assures that represented locale is available (installed) in
 * current FLOW3 installation. In order to check that, various methods of
 * \TYPO3\FLOW3\I18n\Service class can be used.
 *
 * @api
 * @see http://www.unicode.org/reports/tr35/
 * @see \TYPO3\FLOW3\I18n\Service
 */
class Locale {

	/**
	 * Simplified pattern which maches (most) locale identifiers
	 *
	 * @see http://rfc.net/rfc4646.html
	 */
	const PATTERN_MATCH_LOCALEIDENTIFIER = '/^(?P<language>[a-zA-Z]{2,3})(?:[-_](?P<script>[a-zA-Z]{4}))?(?:[-_](?P<region>[a-zA-Z]{2}|[0-9]{3})){0,1}(?:[-_](?P<variant>(?:[a-zA-Z0-9]{5,8})|(?:[0-9][a-zA-Z0-9]{3})))?(?:[-_].+)*$/';

	/**
	 * The language identifier - a BCP47, ISO 639-3 or 639-5 code
	 * Like the standard says, we use "mul" to label multilanguage content
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://en.wikipedia.org/wiki/ISO_639
	 */
	protected $language = NULL;

	/**
	 * The script identifier - an ISO 15924 code according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://unicode.org/iso15924/iso15924-codes.html
	 */
	protected $script = NULL;

	/**
	 * The region identifier - an ISO 3166-1-alpha-2 code or a UN M.49 three digit code
	 * Note: We use "ZZ" for "unknown region" or "global"
	 *
	 * @var string
	 * @see http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm
	 * @see http://en.wikipedia.org/wiki/UN_M.49
	 */
	protected $region = NULL;

	/**
	 * The optional variant identifier - one of the registered registered variants according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 */
	protected $variant = NULL;

	/**
	 * Constructs this locale object
	 *
	 * @param string $localeIdentifier A valid locale identifier according to UTS#35
	 * @throws \InvalidArgumentException When argument is not a string
	 * @throws \TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException If the locale identifier is not valid
	 * @api
	 */
	public function __construct($localeIdentifier) {
		if (!is_string($localeIdentifier)) throw new \InvalidArgumentException('A locale identifier must be of type string, ' . gettype($localeIdentifier) . ' given.', 1221216120);
		if (preg_match(self::PATTERN_MATCH_LOCALEIDENTIFIER, $localeIdentifier, $matches) !== 1) throw new \TYPO3\FLOW3\I18n\Exception\InvalidLocaleIdentifierException('"' . $localeIdentifier . '" is not a valid locale identifier.', 1221137814);

		$this->language = strtolower($matches['language']);
		if (!empty($matches['script'])) $this->script = ucfirst(strtolower($matches['script']));
		if (!empty($matches['region'])) $this->region = strtoupper($matches['region']);
		if (!empty($matches['variant'])) $this->variant = strtoupper($matches['variant']);
	}

	/**
	 * Returns the language defined in this locale
	 *
	 * @return string The language identifier
	 * @api
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Returns the script defined in this locale
	 *
	 * @return string The script identifier
	 * @api
	 */
	public function getScript() {
		return $this->script;
	}

	/**
	 * Returns the region defined in this locale
	 *
	 * @return string The region identifier
	 * @api
	 */
	public function getRegion() {
		return $this->region;
	}

	/**
	 * Returns the variant defined in this locale
	 *
	 * @return string The variant identifier
	 * @api
	 */
	public function getVariant() {
		return $this->variant;
	}

	/**
	 * Returns the string identifier of this locale
	 *
	 * @return string The locale identifier (tag)
	 * @api
	 */
	public function __toString() {
		$localeIdentifier = $this->language;

		if ($this->script !== NULL) $localeIdentifier .= '_' . $this->script;
		if ($this->region !== NULL) $localeIdentifier .= '_' . $this->region;
		if ($this->variant !== NULL) $localeIdentifier .= '_' . $this->variant;

		return $localeIdentifier;
	}
}

?>