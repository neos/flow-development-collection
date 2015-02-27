<?php
namespace TYPO3\Flow\I18n\EelHelper;

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
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\I18n\Service as I1nService;

/**
 * Configuration helpers for Eel contexts
 */
class TranslationHelper implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var Translator
	 */
	protected $translator;

	/**
	 * @Flow\Inject
	 * @var I1nService
	 */
	protected $i18nService;

	/**
	 * Fetches a translation by its id.
	 *
	 * Examples::
	 *
	 *     Translation.translateById('some.title', 'Acme.Site') == 'Acme Inc.'
	 *
	 *     Translation.translateById('str1407180613', 'Acme.Site', 'Ui') == 'Login'
	 *
	 * @param string $id The ID to translate
	 * @param string $packageKey The package key where to find the translation file
	 * @param string $sourceName The source name, defaults to "Main"
	 * @return mixed
	 */
	public function translateById($id, $packageKey, $sourceName = 'Main') {
		return $this->translator->translateById($id, array(), array(), NULL, $sourceName, $packageKey);
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
