<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Fixture::MVC;

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
 * A dummy request processor (-fixture) 
 * 
 * @package		
 * @version 	$Id$
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MockRequestProcessor implements F3::FLOW3::MVC::RequestProcessorInterface {

	/**
	 * Processes the request
	 *
	 * @param  F3::FLOW3::MVC::Request		$request: The request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(F3::FLOW3::MVC::Request $request) {
		$request->setArgument('F3::FLOW3::Fixture::MVC::MockRequestProcessor', 'TRUE');		
	}
	
}
?>