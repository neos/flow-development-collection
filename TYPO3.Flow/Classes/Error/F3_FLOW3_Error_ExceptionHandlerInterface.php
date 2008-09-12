<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Error;

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
 * @subpackage Error
 * @version $Id$
 */

/**
 * Contract for an exception handler
 *
 * @package FLOW3
 * @subpackage Error
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface ExceptionHandlerInterface {

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 */
	public function __construct();

	/**
	 * Handles the given exception
	 *
	 * @param ::Exception $exception: The exception object
	 * @return void
	 */
	public function handleException(::Exception $exception);

}
?>