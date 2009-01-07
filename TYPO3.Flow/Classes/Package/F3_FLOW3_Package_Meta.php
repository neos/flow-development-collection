<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

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
 * @subpackage Package
 * @version $Id$
 */

/**
 * The default TYPO3 Package Meta implementation
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Meta implements \F3\FLOW3\Package\MetaInterface {

	/**
	 * @var	string The package key
	 */
	protected $packageKey;

	/**
	 * @var	\F3\FLOW3\Package\Version	The version number
	 */
	protected $version;

	/**
	 * @var string	Package title
	 */
	protected $title;

	/**
	 * Constructor
	 *
	 * @param string $packageKey The package key
	 * @param \SimpleXMLElement $packageMetaXML If specified, the XML data (which must be valid package meta XML) will be used to set the meta properties
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Validate the $packageMetaXML as soon as we have a DTD / Schema for it
	 */
	public function __construct($packageKey, \SimpleXMLElement $packageMetaXML = NULL) {
		if ($packageMetaXML !== NULL) {
			$this->packageKey = (string)$packageMetaXML->packageKey;
			$this->version = (string)$packageMetaXML->version;
			$this->title = (string)$packageMetaXML->title;
		}
	}

}

?>