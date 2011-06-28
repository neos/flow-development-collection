<?php
namespace TYPO3\FLOW3\Package;

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
 * Documentation for a package
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Documentation {

	/**
	 * Reference to the package of this documentation
	 * @var \TYPO3\FLOW3\Package\PackageInterface
	 */
	protected $package;

	/**
	 * @var string
	 */
	protected $documentationName;

	/**
	 * Absolute path to the documentation
	 * @var string
	 */
	protected $documentationPath;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Package\PackageInterface $package Reference to the package of this documentation
	 * @param string $documentationName Name of the documentation
	 * @param string $documentationPath Absolute path to the documentation directory
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($package, $documentationName, $documentationPath) {
		$this->package = $package;
		$this->documentationName = $documentationName;
		$this->documentationPath = $documentationPath;
	}

	/**
	 * Get the package of this documentation
	 *
	 * @return \TYPO3\FLOW3\Package\PackageInterface The package of this documentation
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getPackage() {
		return $this->package;
	}

	/**
	 * Get the name of this documentation
	 *
	 * @return string The name of this documentation
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getDocumentationName() {
		return $this->documentationName;
	}

	/**
	 * Get the full path to the directory of this documentation
	 *
	 * @return string Path to the directory of this documentation
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getDocumentationPath() {
		return $this->documentationPath;
	}

	/**
	 * Returns the available documentation formats for this documentation
	 *
	 * @return array Array of \TYPO3\FLOW3\Package\DocumentationFormat
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getDocumentationFormats() {
		$documentationFormats = array();

		$documentationFormatsDirectoryIterator = new \DirectoryIterator($this->documentationPath);
		$documentationFormatsDirectoryIterator->rewind();
		while ($documentationFormatsDirectoryIterator->valid()) {
			$filename = $documentationFormatsDirectoryIterator->getFilename();
			if ($filename[0] != '.' && $documentationFormatsDirectoryIterator->isDir()) {
				$documentationFormat = new \TYPO3\FLOW3\Package\Documentation\Format($filename, $this->documentationPath . $filename . '/');
				$documentationFormats[$filename] = $documentationFormat;
			}
			$documentationFormatsDirectoryIterator->next();
		}

		return $documentationFormats;
	}
}
?>