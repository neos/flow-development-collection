<?php
declare(ENCODING = 'utf-8');

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
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Resource_Manager {

	/**
	 * @var F3_FLOW3_Resource_ClassLoader Instance of the class loader
	 */
	protected $classLoader;

	/**
	 * @var F3_FLOW3_Component_Manager
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Cache_VariableCache
	 */
	protected $resourceMetadataCache;

	/**
	 * @var array The loaded resources (identity map)
	 */
	protected $loadedResources = array();

	/**
	 * @var string The base path for the mirrored public assets
	 */
	protected $publicMirrorPath;

	/**
	 * Constructs the resource manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Resource_ClassLoader $classLoader, F3_FLOW3_Component_Manager $componentManager) {
		$this->classLoader = $classLoader;
		$this->componentManager = $componentManager;
		$this->initializeMetadataCache();
		$this->initializeMirrorDirectory();
	}

	/**
	 * Initializes the cache used for storing meta data about resources
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeMetadataCache() {
		$context = $this->componentManager->getContext();
		$cacheBackend = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->resourceMetadataCache = $this->componentManager->getComponent('F3_FLOW3_Cache_VariableCache', 'FLOW3_Resource_Manager', $cacheBackend);
	}

	/**
	 * Determines the path to the asset mirror directory and makes sure it exists
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeMirrorDirectory() {
		$this->publicMirrorPath = FLOW3_PATH_PUBLIC . 'Assets/';
		if (!is_writable($this->publicMirrorPath)) {
			F3_FLOW3_Utility_Files::createDirectoryRecursively($this->publicMirrorPath);
		}
		if (!is_dir($this->publicMirrorPath)) throw new F3_FLOW3_Cache_Exception('The directory "' . $this->publicMirrorPath . '" does not exist.', 1207124538);
		if (!is_writable($this->publicMirrorPath)) throw new F3_FLOW3_Cache_Exception('The directory "' . $this->publicMirrorPath . '" is not writable.', 1207124546);
	}

	/**
	 * Explicitly registers a file path and name which holds the implementation of
	 * the given class.
	 *
	 * @param  string $className: Name of the class to register
	 * @param  string $classFilePathAndName: Absolute path and file name of the file holding the class implementation
	 * @return void
	 * @throws InvalidArgumentException if $className is not a valid string
	 * @throws F3_FLOW3_Resource_Exception_FileDoesNotExist if the specified file does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerClassFile($className, $classFilePathAndName) {
		if (!is_string($className)) throw new InvalidArgumentException('Class name must be a valid string.', 1187009929);
		if (!file_exists($classFilePathAndName)) throw new F3_FLOW3_Resource_Exception_FileDoesNotExist('The specified class file does not exist.', 1187009987);
		$this->classLoader->setSpecialClassNameAndPath($className, $classFilePathAndName);
	}

	/**
	 * Returns a file resource if found using the supplied URI
	 *
	 * @param string $URI
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResource($URI) {
		if(key_exists($URI, $this->loadedResources)) {
			return $this->loadedResources[$URI];
		}

		$identifier = md5($URI);
		if($this->resourceMetadataCache->has($identifier)) {
			$metadata = $this->resourceMetadataCache->load($identifier);
		} else {
			$this->mirrorResource($URI);
			$metadata = $this->extractResourceMetadata($URI);
			$this->resourceMetadataCache->save($identifier, $metadata);
		}

		$this->loadedResources[$URI] = $this->instantiateResource($metadata);
		return $this->loadedResources[$URI];
	}

	/**
	 * instantiates a resource based on the given metadata
	 *
	 * @param array $metadata
	 * @return F3_FLOW3_Resource_AbstractResource
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function instantiateResource(array $metadata) {
		switch($metadata['URI']['scheme']) {
			case 'file':
				$resource = $this->instantiateFileResource($metadata);
				break;
			default:
				throw new F3_FLOW3_Resource_Exception('Scheme "' . $metadata['URI']['scheme'] . '" in URI cannot be handled.', 1207055219);
		}
		return $resource;
	}

	/**
	 * Returns a FileResource object based on stored meta data
	 *
	 * @param string $URI The URI identifying the resource
	 * @return F3_FLOW3_Resource_FileResource
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function instantiateFileResource(array $metadata) {
		return $this->componentManager->getComponent('F3_FLOW3_Resource_FileResource', $metadata);
	}

	/**
	 * Fetches the resource and mirrors it locally
	 *
	 * @param string $URI
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function mirrorResource($URI) {
		$parsedURI = parse_url($URI);
		if(!strlen(basename($parsedURI['path']))) {
			throw new F3_FLOW3_Resource_Exception('The URI "' . $URI . '" does not point to a single resource.', 1207128431);
		}

		switch($parsedURI['scheme']) {
			case 'file':
				if(strlen($parsedURI['host'])) {
					$this->mirrorPackageResource($URI);
				} else {
					throw new F3_FLOW3_Resource_Exception('Currently only in-package resources can be handled.', 1207131018);
				}
				break;
			default:
				throw new F3_FLOW3_Resource_Exception('Scheme in URI "' . $URI . '" cannot be handled.', 1207055219);
		}
	}

	/**
	 * Fetches a package resource and mirrors it locally
	 *
	 * @param array $URI
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function mirrorPackageResource($URI) {
		$parsedURI = parse_url($URI);
		$source = FLOW3_PATH_PACKAGES . $parsedURI['host'] . '/Resources' . $parsedURI['path'];
		$destination = $this->publicMirrorPath . $parsedURI['host'] . '/Resources' . $parsedURI['path'];

		if(!file_exists($source)) {
			throw new F3_FLOW3_Resource_Exception('The resource "' . $URI . '" could not be retrieved.', 1207053263);
		}

		F3_FLOW3_Utility_Files::createDirectoryRecursively(dirname($destination));
		copy($source, $destination);
		if(!file_exists($destination)) {
			throw new F3_FLOW3_Resource_Exception('The resource "' . $parsedURI['path'] . '" could not be mirrored.', 1207127750);
		}
	}

	/**
	 * Fetches and returns metadata for a resource
	 *
	 * @param string $URI
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function extractResourceMetadata($URI) {
		$parsedURI = parse_url($URI);
		$metadata = array();

		$metadata['URI'] = $parsedURI;
		$metadata['path'] = $this->publicMirrorPath . $parsedURI['host'] . '/Resources' . dirname($parsedURI['path']);
		$metadata['name'] = basename($parsedURI['path']);

		return $metadata;
	}
}

?>