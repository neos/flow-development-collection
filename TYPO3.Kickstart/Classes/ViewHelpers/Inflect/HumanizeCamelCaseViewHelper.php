<?php
namespace TYPO3\Kickstart\ViewHelpers\Inflect;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Humanize a camel cased value
 *
 * = Examples =
 *
 * <code title="Example">
 * <k:inflect.humanizeCamelCase>{CamelCasedModelName}</k:inflect.humanizeCamelCase>
 * </code>
 *
 * Output:
 * Camel cased model name
 *
 * @FLOW3\Scope("prototype")
 */
class HumanizeCamelCaseViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var TYPO3\Kickstart\Utility\Inflector
	 * @FLOW3\Inject
	 */
	protected $inflector;

	/**
	 * Humanize a model name
	 *
	 * @param boolean $lowercase Wether the result should be lowercased 
	 * @return string The humanized string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($lowercase = FALSE) {
		$content = $this->renderChildren();
		return $this->inflector->humanizeCamelCase($content, $lowercase);
	}
}
?>