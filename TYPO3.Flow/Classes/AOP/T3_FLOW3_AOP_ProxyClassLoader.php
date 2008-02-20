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
 * Class Loader implementation which loads classes from a dynamic proxy
 * file if such a file exists.
 * 
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_ProxyClassLoader.php 201 2007-03-30 11:18:30Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_ProxyClassLoader {

	/**
	 * Loads php files containing classes found in the AOP Proxy directory
	 * 
	 * @param	string		$className: Name of the class/interface to find a php file for
	 * @return	void
	 * @author	Robert Lemke <robert@typo3.org>
	 */
	public function loadClass($className) {		
		if (file_exists(TYPO3_PATH_PRIVATEFILECACHE . 'FLOW3/AOP/ProxyCache/' . $className . '.php')) {
			require_once(TYPO3_PATH_PRIVATEFILECACHE . 'FLOW3/AOP/ProxyCache/' . $className . '.php');
		}
	}

}

?>