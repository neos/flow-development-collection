<?php
namespace TYPO3\FLOW3\Package\MetaData;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A package meta XML writer implementation based on the Package.xml format
 *
 * @FLOW3\Scope("singleton")
 */
class XmlWriter {

	/**
	 * Write metadata for the given package into a Package.xml file.
	 *
	 * @param \TYPO3\FLOW3\Package\PackageInterface $package The package - also contains information about where to write the Package meta file
	 * @param \TYPO3\FLOW3\Package\MetaDataInterface $meta The MetaData object containing the information to write
	 * @return boolean If writing the XML file was successful returns TRUE, otherwise FALSE
	 */
	static public function writePackageMetaData(\TYPO3\FLOW3\Package\PackageInterface $package, \TYPO3\FLOW3\Package\MetaDataInterface $meta) {
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
				self::writeParty($xml, $party);
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
						self::writeConstraint($xml, $constraint);
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
	 * @param \TYPO3\FLOW3\Package\MetaData\AbstractParty $party The party to write
	 * @return void
	 */
	static protected function writeParty(\XMLWriter $xml, \TYPO3\FLOW3\Package\MetaData\AbstractParty $party) {
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
	 * @param \TYPO3\FLOW3\Package\MetaData\AbstractConstraint $constraint The constraint to write
	 * @return void
	 */
	static protected function writeConstraint(\XMLWriter $xml, \TYPO3\FLOW3\Package\MetaData\AbstractConstraint $constraint) {
		$xml->startElement($constraint->getConstraintScope());

		if (strlen($constraint->getMinVersion())) $xml->writeAttribute('minVersion', $constraint->getMinVersion());
		if (strlen($constraint->getMaxVersion())) $xml->writeAttribute('maxVersion', $constraint->getMaxVersion());

		switch ($constraint->getConstraintScope()) {
			case \TYPO3\FLOW3\Package\MetaData::CONSTRAINT_SCOPE_SYSTEM :
				if (strlen($constraint->getType())) $xml->writeAttribute('type', $constraint->getType());
			break;
			case \TYPO3\FLOW3\Package\MetaData::CONSTRAINT_SCOPE_PACKAGE :
			break;
		}

		if (strlen($constraint->getValue())) $xml->text($constraint->getValue());

		$xml->endElement();
	}

}
?>