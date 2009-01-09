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
 * A HTML Resource
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class HTMLResource extends \F3\FLOW3\Resource\TextResource {

	/**
	 * Allows to set the metadata for this resource.
	 *
	 * @param array $metadata
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMetadata(array $metadata) {
		$this->URI = $metadata['URI'];
		$this->path = $metadata['path'];
		$this->name = $metadata['name'];
		$this->mediaType = $metadata['mediaType'];
		$this->mimeType = $metadata['mimeType'];
	}

	/**
	 * Returns the content of this resource.
	 *
	 * @return string|binary Resource content (HTML)
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getContent() {
		return file_get_contents($this->path . '/' . $this->name);
	}
}

?>