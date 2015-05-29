<?php
namespace TYPO3\Kickstart\ViewHelpers\Inflect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Kickstart".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Humanize a camel cased value
 *
 * = Examples =
 *
 * <code title="Example">
 * {CamelCasedModelName -> k:inflect.humanizeCamelCase()}
 * </code>
 *
 * Output:
 * Camel cased model name
 *
 */
class HumanizeCamelCaseViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\Kickstart\Utility\Inflector
	 * @Flow\Inject
	 */
	protected $inflector;

	/**
	 * Humanize a model name
	 *
	 * @param boolean $lowercase Wether the result should be lowercased
	 * @return string The humanized string
	 */
	public function render($lowercase = FALSE) {
		$content = $this->renderChildren();
		return $this->inflector->humanizeCamelCase($content, $lowercase);
	}
}
