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
	 * @var F3\FLOW3\Object\ObjectFactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectFactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\ObjectFactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
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
	 * @return object An object or an instance of F3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @throws \F3\FLOW3\Resource\Exception if an error with the uploaded file occurred.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function convertFrom($source) {
		if (is_array($source)) {
			if ($source['error'] === \UPLOAD_ERR_NO_FILE) return NULL;
			if ($source['error'] !== \UPLOAD_ERR_OK) return $this->objectFactory->create('F3\FLOW3\Error\Error', \F3\FLOW3\Utility\Files::getUploadErrorMessage($source['error']) , 1264440823);

			$pathInfo = pathinfo($source['name']);
			if (!isset($pathInfo['extension']) || substr(strtolower($pathInfo['extension']), -3, 3) === 'php') {
				return $this->objectFactory->create('F3\FLOW3\Error\Error', 'Invalid resource: ".php" or empty file extensions are not allowed.' , 1260895946);
			}
			$resource = $this->objectFactory->create('F3\FLOW3\Resource\Resource', sha1_file($source['tmp_name']), $pathInfo['extension']);

			$newPathAndFilename = FLOW3_PATH_DATA . 'Persistent/Resources/' . $resource->getHash();
			try {
	 			move_uploaded_file($source['tmp_name'], $newPathAndFilename);
			} catch (\Exception $exception) {
				throw new \F3\FLOW3\Resource\Exception('Could not move uploaded file. (' . $exception->getMessage() . ')', 1260874112);
			}
			return $resource;
		} else {
			return $this->objectFactory->create('F3\FLOW3\Error\Error', 'The source for conversion to a resource object was not an array.' , 1264440811);
		}
	}
}

?>