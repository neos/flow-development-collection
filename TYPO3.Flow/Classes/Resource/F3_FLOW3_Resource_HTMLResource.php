<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

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
 * A HTML Resource
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class HTMLResource extends F3::FLOW3::Resource::TextResource {

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