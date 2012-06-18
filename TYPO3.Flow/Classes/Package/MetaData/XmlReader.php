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
 * A package meta XML reader implementation based on the Package.xml format
 *
 * @FLOW3\Scope("singleton")
 */
class XmlReader {

	/**
	 * Read the package metadata for the given package from the
	 * Package.xml file contained in the package
	 *
	 * @param \TYPO3\FLOW3\Package\PackageInterface $package The package to read metadata for
	 * @return \TYPO3\FLOW3\Package\MetaData A package meta data instance with the data from the package's Package.xml file.
	 */
	static public function readPackageMetaData(\TYPO3\FLOW3\Package\PackageInterface $package) {
		$packageInfoPath = $package->getMetaPath();

		$meta = new \TYPO3\FLOW3\Package\MetaData($package->getPackageKey());

		$xml = simplexml_load_file(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($packageInfoPath, 'Package.xml')));
		if ($xml === FALSE) {
			$meta->setDescription('[Package.xml could not be read.]');
		} else {
			$meta->setVersion((string)$xml->version);
			$meta->setTitle((string)$xml->title);
			$meta->setDescription((string)$xml->description);

			self::readCategories($xml, $meta);

			self::readParties($xml, $meta);

			self::readConstraints($xml, $meta);
		}

		return $meta;
	}

	/**
	 * Read categories from XML
	 *
	 * @param \SimpleXMLElement $xml The XML document
	 * @param \TYPO3\FLOW3\Package\MetaData $meta The meta information
	 * @return void
	 */
	static protected function readCategories(\SimpleXMLElement $xml, \TYPO3\FLOW3\Package\MetaData $meta) {
		if (isset($xml->categories) && count($xml->categories)) {
			foreach ($xml->categories->category as $category) {
				$meta->addCategory((string)$category);
			}
		}
	}

	/**
	 * Read parties (persons and companies) from XML
	 *
	 * @param \SimpleXMLElement $xml The XML document
	 * @param \TYPO3\FLOW3\Package\MetaData $meta The meta information
	 * @return void
	 */
	static protected function readParties(\SimpleXMLElement $xml, \TYPO3\FLOW3\Package\MetaData $meta) {
		if (isset($xml->parties) && count($xml->parties)) {
			if (isset($xml->parties->person) && count($xml->parties->person)) {
				foreach ($xml->parties->person as $person) {
					$role = (string)$person['role'];
					$meta->addParty(new \TYPO3\FLOW3\Package\MetaData\Person($role,
						(string)$person->name, (string)$person->email, (string)$person->website,
						(string)$person->company, (string)$person->repositoryUserName));
				}
			}
			if (isset($xml->parties->company) && count($xml->parties->company)) {
				foreach ($xml->parties->company as $company) {
					$role = (string)$company['role'];
					$meta->addParty(new \TYPO3\FLOW3\Package\MetaData\Company($role,
						(string)$company->name, (string)$company->email, (string)$company->website));
				}
			}
		}
	}

	/**
	 * Read constraints by type and role (package, system) from XML
	 *
	 * @param \SimpleXMLElement $xml The XML document
	 * @param \TYPO3\FLOW3\Package\MetaData $meta The meta information
	 * @return void
	 */
	static protected function readConstraints(\SimpleXMLElement $xml, \TYPO3\FLOW3\Package\MetaData $meta) {
		foreach ($meta->getConstraintTypes() as $constraintType) {
			if ($xml->constraints->{$constraintType}) {
				foreach ($xml->constraints->{$constraintType}->children() as $constraint) {
					switch ((string)$constraint->getName()) {
						case 'package':
							$meta->addConstraint(new \TYPO3\FLOW3\Package\MetaData\PackageConstraint(
								$constraintType, (string)$constraint, (string)$constraint['minVersion'],
								(string)$constraint['maxVersion']));
							break;
						case 'system':
							$meta->addConstraint(new \TYPO3\FLOW3\Package\MetaData\SystemConstraint(
								$constraintType, (string)$constraint['type'], (string)$constraint,
								(string)$constraint['minVersion'], (string)$constraint['maxVersion']));
							break;
					}
				}
			}
		}
	}

}
?>