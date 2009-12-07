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
 * An object converter for Resource objects
 *
 * @version $Id: Mapper.php 3531 2009-11-30 20:46:05Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ResourceObjectConverter implements \F3\FLOW3\Property\ObjectConverterInterface {

	/**
	 * @var F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns a list of fully qualified class names of those classes which are supported
	 * by this property editor.
	 *
	 * @return array<string>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSupportedTypes() {
		return array('F3\FLOW3\Resource\Resource');
	}

	/**
	 * Converts the given string or array to a Resource object.
	 *
	 * If the input format is an array, this method assumes the resource to be a fresh file upload
	 * and moves the temporary upload file to the persistent resources directory.
	 *
	 * @return mixed An object or boolean FALSE if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function convertFrom($source) {
		if (is_array($source)) {
			if (!isset($source['tmp_name'])) return FALSE;
			$hash = sha1_file($source['tmp_name']);
			$newPathAndFilename = FLOW3_PATH_DATA . 'Persistent/Resources/' . $hash;
			move_uploaded_file($source['tmp_name'], $newPathAndFilename);

			return $this->objectManager->getObject('F3\FLOW3\Resource\Resource', $hash);
		} elseif (is_string($source) && strlen($source) === 40) {
			return $this->objectManager->getObject('F3\FLOW3\Resource\Resource', $source);
		} else {
			return FALSE;
		}
	}
}

?>