<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Locale
 * @version $Id:$
 */

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
 * @package FLOW3
 * @subpackage Locale
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see http://www.unicode.org/reports/tr35/
 */
class F3_FLOW3_Locale_Locale {

	/**
	 * Simplified pattern which maches (most) locale identifiers
	 *
	 * @see http://rfc.net/rfc4646.html
	 */
	const PATTERN_MATCH_LOCALEIDENTIFIER = '/^(?P<language>[a-zA-Z]{2,3})(?:[-_](?P<script>[a-zA-Z]{4}))?(?:[-_](?P<region>[a-zA-Z]{2}|[0-9]{3})){0,1}(?:[-_](?P<variant>(?:[a-zA-Z0-9]{5,8})|(?:[0-9][a-zA-Z0-9]{3})))?(?:[-_].+)*$/';

	/**
	 * The language identifier - a BCP47, ISO 639-3 or 639-5 code
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://en.wikipedia.org/wiki/ISO_639
	 */
	protected $language = 'en';

	/**
	 * The script identifier - an ISO 15924 code according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 * @see http://unicode.org/iso15924/iso15924-codes.html
	 */
	protected $script = 'Latn';

	/**
	 * The region identifier - an ISO 3166-1-alpha-2 code or a UN M.49 three digit code
	 *
	 * @var unknown_type
	 * @see http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm
	 * @see http://en.wikipedia.org/wiki/UN_M.49
	 */
	protected $region = 'EN';


	/**
	 * The optional variant identifier - one of the registered registered variants according to BCP47
	 *
	 * @var string
	 * @see http://rfc.net/bcp47.html
	 */
	protected $variant = '';

	/**
	 * Constructs this locale object
	 *
	 * @param string $localeIdentifier A valid locale identifier according to UTS#35
	 * @throws F3_FLOW3_Locale_Exception_InvalidLocaleIdentifier if the locale identifier is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($localeIdentifier) {
		if (preg_match(self::PATTERN_MATCH_LOCALEIDENTIFIER, $localeIdentifier, $matches) === 0) throw new F3_FLOW3_Locale_Exception_InvalidLocaleIdentifier('"' . $localeIdentifier . '" is not a valid locale identifier.', 1221137814);

		$this->language = strtolower($matches['language']);
		if (key_exists('script', $matches)) $this->script = ucfirst(strtolower($matches['script']));
		if (key_exists('region', $matches)) $this->region = strtoupper($matches['region']);
	}

	/**
	 * Returns the language defined in this locale
	 *
	 * @return string The language identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Returns the script defined in this locale
	 *
	 * @return string The script identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScript() {
		return $this->script;
	}

	/**
	 * Returns the region defined in this locale
	 *
	 * @return string The region identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegion() {
		return $this->region;
	}

}
?>