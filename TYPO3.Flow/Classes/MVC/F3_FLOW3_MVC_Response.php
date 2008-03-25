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
 * A generic and very basic response implementation
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:F3_FLOW3_MVC_Response.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class F3_FLOW3_MVC_Response {

	/**
	 * @var string The response content
	 */
	protected $content = NULL;
	
	/**
	 * Overrides and sets the content of the response
	 *
	 * @param  string										$content: The response content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * Appends content to the already existing content.
	 * 
	 * @param  string										$content: More response content
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function appendContent($content) {
		$this->content .= $content;
	}
	
	/**
	 * Returns the response content without sending it.
	 *
	 * @return string										The response content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Sends the response
	 * 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function send() {
		if ($this->content !== NULL) {
			echo $this->getContent();
		}
	}
}

?>