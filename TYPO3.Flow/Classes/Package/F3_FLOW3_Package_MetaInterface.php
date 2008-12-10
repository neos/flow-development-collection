<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Package;

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
 * @subpackage Package
 * @version $Id:F3::FLOW3::Package::MetaInterface.php 203 2007-03-30 13:17:37Z robert $
 */

/**
 * Interface for TYPO3 Package Meta information
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3::FLOW3::Package::MetaInterface.php 203 2007-03-30 13:17:37Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface MetaInterface {

	/**
	 * Constructor
	 *
	 * @param string $packageKey The package key
	 * @param SimpleXMLElement $packageMetaXML If specified, the XML data (which must be valid package meta XML) will be used to set the meta properties
	 * @return void
	 */
	public function __construct($packageKey, ::SimpleXMLElement $packageMetaXML = NULL);

}
?>