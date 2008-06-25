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
 * @subpackage Session
 * @version $Id:$
 */

/**
 * Contract for a simple session.
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Session_PHP implements F3_FLOW3_Session_Interface {

	/**
	 * Constructor.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct() {

	}

	/**
	 * Returns the contents (array) associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return array The contents associated with the given key
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getContentsByKey($key) {

	}
}

?>