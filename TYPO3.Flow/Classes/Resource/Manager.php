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
 * The Resource Manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class Manager {

	/**
	 * Constants reflecting the file caching strategies
	 */
	const CACHE_STRATEGY_NONE = 'none';
	const CACHE_STRATEGY_PACKAGE = 'package';
	const CACHE_STRATEGY_FILE = 'file';

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader Instance of the class loader
	 */
	protected $classLoader;

	/**
	 * @var \F3\FLOW3\Object\Factory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Resource\Publisher
	 */
	protected $resourcePublisher;

	/**
	 * @var array The loaded resources (identity map)
	 */
	protected $loadedResources = array();

	/**
	 * Constructs the resource manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Resource\ClassLoader $classLoader, \F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->classLoader = $classLoader;
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the resource publisher
	 *
	 * @param \F3\FLOW3\Resource\Publisher $resourcePublisher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectResourcePublisher(\F3\FLOW3\Resource\Publisher $resourcePublisher) {
		$this->resourcePublisher = $resourcePublisher;
	}

	/**
	 * Explicitly registers a file path and name which holds the implementation of
	 * the given class.
	 *
	 * @param  string $className: Name of the class to register
	 * @param  string $classFilePathAndName: Absolute path and file name of the file holding the class implementation
	 * @return void
	 * @throws \InvalidArgumentException if $className is not a valid string
	 * @throws \F3\FLOW3\Resource\Exception\FileDoesNotExist if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerClassFile($className, $classFilePathAndName) {
		if (!is_string($className)) throw new \InvalidArgumentException('Class name must be a valid string.', 1187009929);
		if (!file_exists($classFilePathAndName)) throw new \F3\FLOW3\Resource\Exception\FileDoesNotExist('The specified class file does not exist.', 1187009987);
		$this->classLoader->setSpecialClassNameAndPath($className, $classFilePathAndName);
	}

	/**
	 * Returns a file resource if found using the supplied URI
	 *
	 * @param \F3\FLOW3\Property\DataType\URI|string $URI
	 * @return \F3\FLOW3\Resource\ResourceInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResource($URI) {
		$URIString = (string)$URI;

		if (isset($this->loadedResources[$URIString])) {
			return $this->loadedResources[$URIString];
		}

		if (is_string($URI)) {
			$URI = $this->objectFactory->create('F3\FLOW3\Property\DataType\URI', $URI);
		}

		$metadata = $this->resourcePublisher->getMetadata($URI);
		$this->loadedResources[$URIString] = $this->instantiateResource($metadata);

		return $this->loadedResources[$URIString];
	}

	/**
	 * Instantiates a resource based on the given metadata
	 *
	 * @param array $metadata
	 * @return \F3\FLOW3\Resource\ResourceInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function instantiateResource(array $metadata) {
		switch ($metadata['mimeType']) {
			case 'text/html':
				$resource = $this->objectFactory->create('F3\FLOW3\Resource\HTMLResource');
				break;
			default:
				throw new \F3\FLOW3\Resource\Exception('Scheme "' . $metadata['URI']->getScheme() . '" in URI cannot be handled.', 1207055219);
		}
		$resource->setMetaData($metadata);
		return $resource;
	}

}

?>