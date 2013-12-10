<?php
namespace TYPO3\Kickstart\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Kickstart".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Wrapper for PHPs ltrim function.
 * @see http://www.php.net/manual/en/ltrim
 *
 * = Examples =
 *
 * <code title="Example">
 * {someVariable -> k:format.ltrim()}
 * </code>
 *
 * Output:
 * content of {someVariable} with ltrim applied
 *
 */
class LtrimViewHelper extends AbstractViewHelper {

	/**
	 * @param string $charlist
	 * @return string The altered string.
	 */
	public function render($charlist = NULL) {
		$content = $this->renderChildren();
		return ltrim($content, $charlist);
	}
}
?>