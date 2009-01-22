<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\Meta;

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
 * A package meta XML reader implementation based on the Package.xml format
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XMLReader implements \F3\FLOW3\Package\Meta\ReaderInterface {

	/**
	 * Read the package metadata for the given package from the
	 * Package.xml file contained in the package
	 *
	 * @param \F3\FLOW3\Package\PackageInterface $package The package to read metadata for
	 * @return Meta A package meta instance with the metadata from the package.xml file.
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function readPackageMeta(\F3\FLOW3\Package\PackageInterface $package) {
		$packageInfoPath = $package->getPackageMetaPath();

		$xml = simplexml_load_file($packageInfoPath);

		$meta = new \F3\FLOW3\Package\Meta($package->getPackageKey());

		$meta->setVersion((string)$xml->version);
		$meta->setTitle((string)$xml->title);
		$meta->setDescription((string)$xml->description);
		$meta->setState((string)$xml->state);

		$this->readCategories($xml, $meta);

		$this->readParties($xml, $meta);

		$this->readConstraints($xml, $meta);

		return $meta;
	}

	/**
	 * Read categories from XML
	 *
	 * @param \SimpleXMLElement $xml The XML document
	 * @param \F3\FLOW3\Package\Meta $meta The meta information
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function readCategories(\SimpleXMLElement $xml, \F3\FLOW3\Package\Meta $meta) {
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
	 * @param \F3\FLOW3\Package\Meta $meta The meta information
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function readParties(\SimpleXMLElement $xml, \F3\FLOW3\Package\Meta $meta) {
		if (isset($xml->parties) && count($xml->parties)) {
			if (isset($xml->parties->person) && count($xml->parties->person)) {
				foreach ($xml->parties->person as $person) {
					$role = (string)$person['role'];
					$meta->addParty(new \F3\FLOW3\Package\Meta\Person($role,
						(string)$person->name, (string)$person->email, (string)$person->website,
						(string)$person->company, (string)$person->repositoryUserName));
				}
			}
			if (isset($xml->parties->company) && count($xml->parties->company)) {
				foreach ($xml->parties->company as $company) {
					$role = (string)$company['role'];
					$meta->addParty(new \F3\FLOW3\Package\Meta\Company($role,
						(string)$company->name, (string)$company->email, (string)$company->website));
				}
			}
		}
	}

	/**
	 * Read constraints by type and role (package, system) from XML
	 *
	 * @param \SimpleXMLElement $xml The XML document
	 * @param \F3\FLOW3\Package\Meta $meta The meta information
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function readConstraints(\SimpleXMLElement $xml, \F3\FLOW3\Package\Meta $meta) {
		foreach ($meta->getConstraintTypes() as $constraintType) {
			if ($xml->constraints->{$constraintType}) {
				foreach ($xml->constraints->{$constraintType}->children() as $constraint) {
					switch ((string)$constraint->getName()) {
						case 'package':
							$meta->addConstraint(new \F3\FLOW3\Package\Meta\PackageConstraint(
								$constraintType, (string)$constraint, (string)$constraint['minVersion'],
								(string)$constraint['maxVersion']));
							break;
						case 'system':
							$meta->addConstraint(new \F3\FLOW3\Package\Meta\SystemConstraint(
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