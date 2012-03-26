<?php
namespace TYPO3\FLOW3\Mvc;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A generic and very basic response implementation
 *
 * @api
 */
interface ResponseInterface {

	/**
	 * Overrides and sets the content of the response
	 *
	 * @param string $content The response content
	 * @return void
	 * @api
	 */
	public function setContent($content);

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return void
	 * @api
	 */
	public function appendContent($content);

	/**
	 * Returns the response content without sending it.
	 *
	 * @return string The response content
	 * @api
	 */
	public function getContent();

	/**
	 * Sends the response
	 *
	 * @return void
	 * @api
	 */
	public function send();
}
?>