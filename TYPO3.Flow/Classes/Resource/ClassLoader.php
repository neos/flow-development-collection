<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassLoader {

	/**
	 * Class names and their absolute path and filename of specifically registered classes. Used for classes which don't follow the \F3\Package\Object scheme.
	 * @var array
	 */
	protected $specialClassNamesAndPaths = array();

	/**
	 * An array of \F3\FLOW3\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadClass($className) {
		if (isset($this->specialClassNamesAndPaths[$className])) {
			$classFilePathAndName = $this->specialClassNamesAndPaths[$className];
		} else {
			$classNameParts = explode('\\', $className);
			if (is_array($classNameParts) && $classNameParts[0] === 'F3' && isset($this->packages[$classNameParts[1]])) {
				$classFilePathAndName = $this->packages[$classNameParts[1]]->getClassesPath();
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= end($classNameParts) . '.php';
			}
		}
		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) require($classFilePathAndName);
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \F3\FLOW3\Package\Package objects
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

	/**
	 * Explicitly sets a file path and name which holds the implementation of
	 * the given class.
	 *
	 * @param string $className Name of the class to register
	 * @param string $classFilePathAndName Absolute path and file name of the file holding the class implementation
	 * @return void
	 * @throws \InvalidArgumentException if $className is not a valid string
	 * @throws \F3\FLOW3\Resource\Exception if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSpecialClassNameAndPath($className, $classFilePathAndName) {
		if (!is_string($className)) throw new \InvalidArgumentException('Class name must be a valid string.', 1187009929);
		if (!file_exists($classFilePathAndName)) throw new \F3\FLOW3\Resource\Exception('The specified class file does not exist.', 1187009987);
		$this->specialClassNamesAndPaths[$className] = $classFilePathAndName;
	}
}

?>