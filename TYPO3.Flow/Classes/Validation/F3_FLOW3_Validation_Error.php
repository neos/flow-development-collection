<?php
declare(encoding = 'utf-8');

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
 * Objects of this kind contain a list of validation errors which occurred during
 * validation.
 * 
 * @package		FLOW3
 * @subpackage	Validation
 * @version 	$Id: F3_FLOW3_Validation_Errors.php 401 2007-12-14 11:09:22Z Andi $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Error extends F3_FLOW3_Error_Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown validation error';
	
	/**
	 * @var string The error code
	 */
	protected $code = 1201447005;
}

?>