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
 * A base Resource class
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class F3_FLOW3_Resource_AbstractResource implements F3_FLOW3_Resource_ResourceInterface {

	/**
	 * @var F3_FLOW3_Property_DataType_URI
	 */
	protected $URI;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $mediaType;

	/**
	 * @var string
	 */
	protected $mimeType;

	/**
	 * Imports content from an URI
	 *
	 * @param F3_FLOW3_Property_DataType_URI $URI
	 * @return boolean TRUE or FALSE depending on import success
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function importFromURI(F3_FLOW3_Property_DataType_URI $URI) {
		return FALSE;
	}

	/**
	 * Returns the type of source the resource originates
	 *
	 * @return string Type, e.g. file, http, ftp, ...
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getDataSourceType() {
		return $this->URI->getScheme();
	}

	/**
	 * The URI representing
	 *
	 * @return F3_FLOW3_Property_DataType_URI
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getURI() {
		return $this->URI;
	}

	/**
	 * Returns the name the resource was obtained from
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the path the resource was obtained from
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getPath() {
		return $this->path;
	}


	/**
	 * Returns the media type of the resource
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMediaType() {
		return $this->mediaType;
	}

	/**
	 * Returns the MIME type of the resource
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMIMEType() {
		return $this->mimeType;
	}

}

?>