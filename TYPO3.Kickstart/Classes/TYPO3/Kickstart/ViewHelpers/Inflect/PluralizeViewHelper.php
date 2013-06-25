<?php
namespace TYPO3\Kickstart\ViewHelpers\Inflect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Kickstart".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Pluralize a word
 *
 * = Examples =
 *
 * <code title="Example">
 * {variable -> k:inflect.pluralize()}
 * </code>
 *
 * Output:
 * content of {variable} in its plural form (foo => foos)
 *
 */
class PluralizeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\Kickstart\Utility\Inflector
	 * @Flow\Inject
	 */
	protected $inflector;

	/**
	 * Pluralize a word
	 *
	 * @return string The pluralized string
	 */
	public function render() {
		$content = $this->renderChildren();
		return $this->inflector->pluralize($content);
	}
}
?>