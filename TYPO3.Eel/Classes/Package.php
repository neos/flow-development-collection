<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Package\Package as BasePackage;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Package base class of the Eel package.
 *
 * @FLOW3\Scope("singleton")
 */
class Package extends BasePackage {

	// TODO: Improve this eel expression recognizer such that "}" inside quotes
	// does not abort the eel expression
	const EelExpressionRecognizer = '/
		^\${
		([^}]*)
		}$/x';
}
?>