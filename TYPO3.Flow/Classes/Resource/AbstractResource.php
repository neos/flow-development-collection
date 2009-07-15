<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * @subpackage Resource
 * @version $Id$
 */

/**
 * A base Resource class
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class AbstractResource implements \F3\FLOW3\Resource\ResourceInterface {

	/**
	 * @var \F3\FLOW3\Property\DataType\URI
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
	 * @param \F3\FLOW3\Property\DataType\URI $URI
	 * @return boolean TRUE or FALSE depending on import success
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function importFromURI(\F3\FLOW3\Property\DataType\URI $URI) {
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
	 * @return \F3\FLOW3\Property\DataType\URI
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
	 * Returns the path the resource was obtained from including file name
	 *
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getPathAndFileName() {
		return \F3\FLOW3\Utility\Files::concatenatePaths(array($this->path, $this->name));
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
