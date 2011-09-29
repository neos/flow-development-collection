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

/**
 * Pluralize a word
 *
 * = Examples =
 *
 * <code title="Example">
 * <k:inflect.pluralize>foo</k:inflect.pluralize>
 * </code>
 *
 * Output:
 * foos
 *
 * @scope prototype
 */
class PluralizeViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var TYPO3\Kickstart\Utility\Inflector
	 * @inject
	 */
	protected $inflector;

	/**
	 * Pluralize a word
	 *
	 * @return string The pluralized string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render() {
		$content = $this->renderChildren();
		return $this->inflector->pluralize($content);
	}
}
?>