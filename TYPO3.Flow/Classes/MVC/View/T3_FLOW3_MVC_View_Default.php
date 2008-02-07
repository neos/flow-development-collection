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
 * The default view - a special case.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_View_Default.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authorst
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_View_Default extends T3_FLOW3_MVC_View_Abstract {
	
	/**
	 * @var T3_FLOW3_MVC_Request
	 */
	protected $request;
	
	/**
	 * Sets the request
	 * 
	 * @param  T3_FLOW3_MVC_Request			$request: The request
	 * @return void
	 */
	public function setRequest(T3_FLOW3_MVC_Request $request) {
		$this->request = $request;
	}
	
	/**
	 * Renders the default view
	 *
	 * @return string				The rendered view
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_MVC_Exception if no request has been set
	 */
	public function render() {
		if (!is_object($this->request)) throw new T3_FLOW3_MVC_Exception('Can\'t render view without request object.', 1192450280);
		$template = file_get_contents(dirname(__FILE__) . '/../../../Resources/Public/MVC/DefaultView_Template.html');
		return str_replace('###BASE_URI###', $this->request->getBaseURI(), $template);
	}

}

?>