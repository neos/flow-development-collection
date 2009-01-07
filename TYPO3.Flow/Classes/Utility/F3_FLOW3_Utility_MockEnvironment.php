<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:\F3\FLOW3\Utility\MockEnvironment.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A subclass of "Evironment" which allows for modifying the underlying environment
 * information. This object should only be used as a mock object for unit testing.
 *
 * @package FLOW3
 * @subpackage Utility
 * @version $Id:\F3\FLOW3\Utility\MockEnvironment.php 467 2008-02-06 19:34:56Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class MockEnvironment extends \F3\FLOW3\Utility\Environment {

	/**
	 * @var array A local copy of the _SERVER super global.
	 */
	public $SERVER;

	/**
	 * @var array A local copy of the _GET super global.
	 */
	public $GET;

	/**
	 * @var array A local copy of the _POST super global.
	 */
	public $POST;

	/**
	 * @var string A lower case string specifying the currently used Server API. See php_sapi_name()/PHP_SAPI for possible values.
	 */
	public $SAPIName;

}
?>