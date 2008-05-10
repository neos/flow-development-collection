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
 * @subpackage Configuration
 */

/**
 * Configuration source based on PHP files
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Configuration_Source_PHP implements F3_FLOW3_Configuration_SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content in a
	 * configuration container. If the file does not exist or could not be loaded,
	 * the empty configuration container is returned.
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load
	 * @return F3_FLOW3_Configuration_Container
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function load($pathAndFilename) {
		$c = new F3_FLOW3_Configuration_Container();
		if (file_exists($pathAndFilename)) {
			require ($pathAndFilename);
		}
		return $c;
	}
}
?>