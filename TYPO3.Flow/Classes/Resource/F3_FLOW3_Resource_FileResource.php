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
 * A file resource
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Resource_FileResource extends SplFileObject {

	/**
	 * Constructs this file resource
	 *
	 * @param array $metadata Metadata for the file
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $metadata) {
		parent::__construct($metadata['path'] . '/' . $metadata['name']);
		$this->metadata = $metadata;
	}

	/**
	 * Returns the content of the resource represented by this object
	 *
	 * @return string|binary The content of the resource
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getContent() {
		return $this->fpassthru();
	}

}
?>