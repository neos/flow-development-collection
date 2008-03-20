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
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Configuration_Source_PHP implements T3_FLOW3_Configuration_SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content in a
	 * configuration container
	 *
	 * @param string $pathAndFilename Full path and file name of the file to load
	 * @return T3_FLOW3_Configuration_Container
	 * @throws T3_FLOW3_Configuration_Exception_NoSuchFile if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function load($pathAndFilename) {
		if (!file_exists($pathAndFilename)) throw new T3_FLOW3_Configuration_Exception_NoSuchFile('File "' . $pathAndFilename . '" does not not exist.', 1206030949);
		$c = new T3_FLOW3_Configuration_Container();
		require ($pathAndFilename);
		return $c;
	}
}
?>