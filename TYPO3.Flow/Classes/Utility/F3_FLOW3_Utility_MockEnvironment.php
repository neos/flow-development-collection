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
 * A subclass of "Evironment" which allows for modifying the underlying environment
 * information. This component should only be used as a mock object for unit testing.
 * 
 * @package 	FLOW3
 * @subpackage	Utility
 * @version     $Id:F3_FLOW3_Utility_MockEnvironment.php 467 2008-02-06 19:34:56Z robert $
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Utility_MockEnvironment extends F3_FLOW3_Utility_Environment {

	/**
	 * @var array A local copy of the _SERVER super global.
	 */
	public $SERVER;
	
	/**
	 * @var array A local copy of the _POST super global.
	 */
	public $POST;
	
	/**
	 * @var string A lower case string specifying the currently used Server API. See php_sapi_name() for possible values.
	 */
	public $SAPIName;
	
}
?>