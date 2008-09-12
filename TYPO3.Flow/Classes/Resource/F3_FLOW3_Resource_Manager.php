<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 */

/**
 * The Resource Manager
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope singleton
 */
class Manager {

	/**
	 * Constants reflecting the file caching strategies
	 */
	const CACHE_STRATEGY_NONE = 1;
	const CACHE_STRATEGY_PACKAGE = 2;
	const CACHE_STRATEGY_FILE = 3;

	/**
	 * @var F3::FLOW3::Resource::ClassLoader Instance of the class loader
	 */
	protected $classLoader;

	/**
	 * @var F3::FLOW3::Component::Factory
	 */
	protected $componentFactory;

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
	public function __construct(F3::FLOW3::Resource::ClassLoader $classLoader, F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->classLoader = $classLoader;
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Explicitly registers a file path and name which holds the implementation of
	 * the given class.
	 *
	 * @param  string $className: Name of the class to register
	 * @param  string $classFilePathAndName: Absolute path and file name of the file holding the class implementation
	 * @return void
	 * @throws InvalidArgumentException if $className is not a valid string
	 * @throws F3::FLOW3::Resource::Exception::FileDoesNotExist if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerClassFile($className, $classFilePathAndName) {
		if (!is_string($className)) throw new InvalidArgumentException('Class name must be a valid string.', 1187009929);
		if (!file_exists($classFilePathAndName)) throw new F3::FLOW3::Resource::Exception::FileDoesNotExist('The specified class file does not exist.', 1187009987);
		$this->classLoader->setSpecialClassNameAndPath($className, $classFilePathAndName);
	}

	/**
	 * Returns a file resource if found using the supplied URI
	 *
	 * @param F3::FLOW3::Property::DataType::URI|string $URI
	 * @return F3::FLOW3::Resource::ResourceInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResource($URI) {
		if (is_string($URI)) {
			$URI = $this->componentFactory->getComponent('F3::FLOW3::Property::DataType::URI', $URI);
		}
		$URIString = (string)$URI;

		if (key_exists($URIString, $this->loadedResources)) {
			return $this->loadedResources[$URIString];
		}

		$metadata = $this->componentFactory->getComponent('F3::FLOW3::Resource::Publisher')->getMetadata($URI);
		$this->loadedResources[$URIString] = $this->instantiateResource($metadata);

		return $this->loadedResources[$URIString];
	}

	/**
	 * Instantiates a resource based on the given metadata
	 *
	 * @param array $metadata
	 * @return F3::FLOW3::Resource::ResourceInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function instantiateResource(array $metadata) {
		switch ($metadata['mimeType']) {
			case 'text/html':
				$resource = $this->componentFactory->getComponent('F3::FLOW3::Resource::HTMLResource');
				break;
			default:
				throw new F3::FLOW3::Resource::Exception('Scheme "' . $metadata['URI']->getScheme() . '" in URI cannot be handled.', 1207055219);
		}
		$resource->setMetaData($metadata);
		return $resource;
	}

}

?>