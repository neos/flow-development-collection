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
	 * @var \F3\FLOW3\Resource\ResourceManager
	 */
	protected $resourceManager;

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
	 * Injects the resource manager
	 *
	 * @param \F3\FLOW3\Resource\ResourceManager $resourceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectResourceManager(\F3\FLOW3\Resource\ResourceManager $resourceManager) {
		$this->resourceManager = $resourceManager;
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
	 * If the input format is an array, this method assumes the resource to be a
	 * fresh file upload and imports the temporary upload file through the
	 * resource manager.
	 *
	 * @param array $source The upload info (expected keys: error, name, tmp_name)
	 * @return object An object or an instance of F3\FLOW3\Error\Error if the input format is not supported or could not be converted for other reasons
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function convertFrom($source) {
		if (is_array($source)) {
			if ($source['error'] === \UPLOAD_ERR_NO_FILE) return NULL;
			if ($source['error'] !== \UPLOAD_ERR_OK) return $this->objectFactory->create('F3\FLOW3\Error\Error', \F3\FLOW3\Utility\Files::getUploadErrorMessage($source['error']) , 1264440823);

			$resource = $this->resourceManager->importUploadedResource($source);
			if ($resource === FALSE) {
				return $this->objectFactory->create('F3\FLOW3\Error\Error', 'The resource manager could not create a resource instance.' , 1264517906);
			} else {
				return $resource;
			}
		} else {
			return $this->objectFactory->create('F3\FLOW3\Error\Error', 'The source for conversion to a resource object was not an array.' , 1264440811);
		}
	}
}

?>