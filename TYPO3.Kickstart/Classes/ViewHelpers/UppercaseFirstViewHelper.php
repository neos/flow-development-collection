<?php
namespace TYPO3\Kickstart\ViewHelpers;

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
 * Wrapper for PHPs ucfirst function.
 * @see http://www.php.net/manual/en/ucfirst
 *
 * = Examples =
 *
 * <code title="Example">
 * <k:uppercaseFirst>{textWithMixedCase}</k:uppercaseFirst>
 * </code>
 *
 * Output:
 * TextWithMixedCase
 *
 * @FLOW3\Scope("prototype")
 */
class UppercaseFirstViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Uppercase first character
	 *
	 * @return string The altered string.
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render() {
		$content = $this->renderChildren();
		return ucfirst($content);
	}
}
?>