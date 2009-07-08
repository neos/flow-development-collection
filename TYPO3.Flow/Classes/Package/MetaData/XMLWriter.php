<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\MetaData;

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
 * @version $Id:F3\FLOW3\Package\.php 203 2007-03-30 13:17:37Z robert $
 */

/**
 * A package meta XML writer implementation based on the Package.xml format
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3\FLOW3\Package\.php 203 2007-03-30 13:17:37Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XMLWriter implements \F3\FLOW3\Package\MetaData\WriterInterface {

	/**
	 * Write metadata for the given package into a Package.xml file.
	 *
	 * @param \F3\FLOW3\Package\PackageInterface $package The package - also contains information about where to write the Package meta file
	 * @param \F3\FLOW3\Package\MetaDataInterface $meta The MetaData object containing the information to write
	 * @return boolean If writing the XML file was successful returns TRUE, otherwise FALSE
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function writePackageMetaData(\F3\FLOW3\Package\PackageInterface $package, \F3\FLOW3\Package\MetaDataInterface $meta) {
		$xml = new \XMLWriter();
		if ($xml->openURI($package->getMetaPath() . 'Package.xml') === FALSE) return FALSE;

		$xml->setIndent(true);
		$xml->setIndentString(chr(9));

		$xml->startDocument('1.0', 'utf-8', 'yes');
		$xml->startElement('package');

		$xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$xml->writeAttribute('xmlns', 'http://typo3.org/ns/2008/flow3/package');
		$xml->writeAttribute('version', '1.0');

		$xml->writeElement('key', $meta->getPackageKey());
		$xml->writeElement('title', $meta->getTitle());
		$xml->writeElement('description', $meta->getDescription());
		$xml->writeElement('version', $meta->getVersion());

		if (count($meta->getCategories())) {
			$xml->startElement('categories');
			foreach ($meta->getCategories() as $category) {
				$xml->writeElement('category', $category);
			}
			$xml->endElement();
		}

		if (count($meta->getParties())) {
			$xml->startElement('parties');
			foreach ($meta->getParties() as $party) {
				$this->writeParty($xml, $party);
			}
			$xml->endElement();
		}

		if (count($meta->getConstraints())) {
			$xml->startElement('constraints');
			foreach ($meta->getConstraintTypes() as $constraintType) {
				$constraints = $meta->getConstraintsByType($constraintType);
				if (count($constraints)) {
					$xml->startElement($constraintType);
					foreach ($constraints as $constraint) {
						$this->writeConstraint($xml, $constraint);
					}
					$xml->endElement();
				}
			}
			$xml->endElement();
		}
		$xml->endElement();
		return TRUE;
	}


	/**
	 * Write party metadata to the XMLWriter.
	 *
	 *
	 * @param \XMLWriter $xml The XMLWriter to write to
	 * @param \F3\FLOW3\Package\MetaData\AbstractParty $party The party to write
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	protected function writeParty(\XMLWriter $xml, \F3\FLOW3\Package\MetaData\AbstractParty $party) {
		$xml->startElement($party->getPartyType());

		if (strlen($party->getRole())) $xml->writeAttribute('role', $party->getRole());
		if (strlen($party->getName())) $xml->writeElement('name', $party->getName());
		if (strlen($party->getEmail())) $xml->writeElement('email', $party->getEmail());
		if (strlen($party->getWebsite())) $xml->writeElement('website', $party->getWebsite());

		switch ($party->getPartyType()) {
			case 'person':
				if (strlen($party->getCompany())) $xml->writeElement('company', $party->getCompany());
				if (strlen($party->getRepositoryUserName())) $xml->writeElement('repositoryUserName', $party->getRepositoryUserName());
			break;
			case 'company':
			break;
		}

		$xml->endElement();
	}

	/**
	 * Write the constraint to a XMLWriter instance.
	 *
	 * @param \XMLWriter $xml The XMLWriter to write to
	 * @param \F3\FLOW3\Package\MetaData\AbstractConstraint $constraint The constraint to write
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @internal
	 */
	protected function writeConstraint(\XMLWriter $xml, \F3\FLOW3\Package\MetaData\AbstractConstraint $constraint) {
		$xml->startElement($constraint->getConstraintScope());

		if (strlen($constraint->getMinVersion())) $xml->writeAttribute('minVersion', $constraint->getMinVersion());
		if (strlen($constraint->getMaxVersion())) $xml->writeAttribute('maxVersion', $constraint->getMaxVersion());

		switch ($constraint->getConstraintScope()) {
			case \F3\FLOW3\Package\MetaData::CONSTRAINT_SCOPE_SYSTEM :
				if(strlen($constraint->getType())) $xml->writeAttribute('type', $constraint->getType());
			break;
			case \F3\FLOW3\Package\MetaData::CONSTRAINT_SCOPE_PACKAGE :
			break;
		}

		if (strlen($constraint->getValue())) $xml->text($constraint->getValue());

		$xml->endElement();
	}

}
?>