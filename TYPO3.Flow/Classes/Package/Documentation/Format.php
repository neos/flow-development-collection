<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\Documentation;

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
 * Documentation format of a documentation
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Format {

	/**
	 * @var string The format name
	 */
	protected $formatName;

	/**
	 * @var string Absolute path to the documentation format
	 */
	protected $formatPath;

	/**
	 * @var \F3\FLOW3\Object\ObjectFactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Constructor
	 *
	 * @param string $formatName Name of the documentation format
	 * @param string $formatPath Absolute path to the documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($formatName, $formatPath) {
		$this->formatName = $formatName;
		$this->formatPath = $formatPath;
	}

	/**
	 * Injects the Object Factory
	 *
	 * @param \F3\FLOW3\Object\ObjectFactoryInterface $objectFactory
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\ObjectFactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Get the name of this documentation format
	 *
	 * @return string The name of this documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getFormatName() {
		return $this->formatName;
	}

	/**
	 * Get the full path to the directory of this documentation format
	 *
	 * @return string Path to the directory of this documentation format
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getFormatPath() {
		return $this->formatPath;
	}

	/**
	 * Returns the available languages for this documentation format
	 *
	 * @return array Array of string language codes
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function getAvailableLanguages() {
		$languages = array();

		$languagesDirectoryIterator = new \DirectoryIterator($this->formatPath);
		$languagesDirectoryIterator->rewind();
		while ($languagesDirectoryIterator->valid()) {
			$filename = $languagesDirectoryIterator->getFilename();
			if ($filename[0] != '.' && $languagesDirectoryIterator->isDir()) {
				$language = $filename;
				$languages[] = $language;
			}
			$languagesDirectoryIterator->next();
		}

		return $languages;
	}
}
?>