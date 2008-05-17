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
 * @subpackage Property
 * @version $Id: F3_FLOW3_Property_MappingWarning.php 681 2008-04-02 14:00:27Z andi $
 */

/**
 * This object holds a mapping warning.
 *
 * @package FLOW3
 * @subpackage Property
 * @version $Id: F3_FLOW3_Property_MappingWarning.php 681 2008-04-02 14:00:27Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Property_MappingWarning extends F3_FLOW3_Error_Warning {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Unknown mapping warning';

	/**
	 * @var string The error code
	 */
	protected $code = 1210351446;
}

?>