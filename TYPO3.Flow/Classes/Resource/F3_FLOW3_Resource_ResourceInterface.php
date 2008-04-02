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
 * @subpackage Resource
 * @version $Id$
 */

/**
 *
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Resource_ResourceInterface {

	/**
	 * Returns the type of source the resource originates
	 *
	 * @return string Type, e.g. file, http, ftp, ...
	 */
	public function getDataSourceType();

	/**
	 * The URI representing
	 *
	 * @return F3_FLOW3
	 */
	public function getURI();

	/**
	 * Returns the name the resource was obtained from
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the path the resource was obtained from
	 *
	 * @return string
	 */
	public function getPath();


	/**
	 * Returns the media type of the resource
	 *
	 * @return string
	 */
	public function getMediaType();

	/**
	 * Returns the MIME type of the resource
	 *
	 * @return string
	 */
	public function getMIMEType();


	/**
	 * Returns the content represented by the resource object
	 *
	 * @return string|binary
	 */
	public function getContent();
}

?>