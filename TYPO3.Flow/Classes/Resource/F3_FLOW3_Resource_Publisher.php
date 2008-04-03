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
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Support functions for handling assets
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Resource_Publisher {

	/**
	 * @var F3_FLOW3_Configuration_Container The FLOW3 base configuration
	 */
	protected $configuration;

	/**
	 * @var string The base path for the mirrored public assets
	 */
	protected $publicResourcePath;

	/**
	 * @var F3_FLOW3_Cache_VariableCache
	 */
	protected $resourceMetadataCache;

	/**
	 * Constructs the Publisher
	 *
	 * @param F3_FLOW3_Component_Manager $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;

		$configurationManager = new F3_FLOW3_Configuration_Manager($this->componentManager->getContext());
		$this->configuration = $configurationManager->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_SETTINGS);

		$this->initializeMirrorDirectory();
		$this->initializeMetadataCache();
	}

	/**
	 * Determines the path to the asset mirror directory and makes sure it exists
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function initializeMirrorDirectory() {
		$this->publicResourcePath = $this->configuration['resourceManager']['publicResourcePath'];
		if (!is_writable($this->publicResourcePath)) {
			F3_FLOW3_Utility_Files::createDirectoryRecursively($this->publicResourcePath);
		}
		if (!is_dir($this->publicResourcePath)) throw new F3_FLOW3_Cache_Exception('The directory "' . $this->publicResourcePath . '" does not exist.', 1207124538);
		if (!is_writable($this->publicResourcePath)) throw new F3_FLOW3_Cache_Exception('The directory "' . $this->publicResourcePath . '" is not writable.', 1207124546);
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
	 * Returns metadata for the resource identified by URI
	 *
	 * @param F3_FLOW3_Property_DataType_URI $URI
	 * @return unknown
	 */
	public function getMetadata(F3_FLOW3_Property_DataType_URI $URI) {
		$metadata = array();
		$identifier = md5((string)$URI);
		if($this->resourceMetadataCache->has($identifier)) {
			$metadata = $this->resourceMetadataCache->load($identifier);
		} else {
			$this->mirrorResource($URI);
			$metadata = $this->extractResourceMetadata($URI);
			$this->resourceMetadataCache->save($identifier, $metadata);
		}
		return $metadata;
	}

	/**
	 * Fetches the resource and mirrors it locally
	 *
	 * @param F3_FLOW3_Property_DataType_URI $URI
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mirrorResource(F3_FLOW3_Property_DataType_URI $URI) {
		if(!strlen(basename($URI->getPath()))) {
			throw new F3_FLOW3_Resource_Exception('The URI "' . $URI . '" does not point to a single resource.', 1207128431);
		}

		switch($URI->getScheme()) {
			case 'file':
				if(strlen($URI->getHost())) {
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
	 * @param F3_FLOW3_Property_DataType_URI $URI
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mirrorPackageResource(F3_FLOW3_Property_DataType_URI $URI) {
		$source = FLOW3_PATH_PACKAGES . $URI->getHost() . '/Resources' . $URI->getPath();
		$destination = $this->publicResourcePath . $URI->getHost() . '/Resources' . $URI->getPath();

		if(!file_exists($source)) {
			throw new F3_FLOW3_Resource_Exception('The resource "' . $URI . '" could not be retrieved.', 1207053263);
		}

		F3_FLOW3_Utility_Files::createDirectoryRecursively(dirname($destination));
		copy($source, $destination);
		if(!file_exists($destination)) {
			throw new F3_FLOW3_Resource_Exception('The resource "' . $URI->getPath() . '" could not be mirrored.', 1207127750);
		}
	}

	/**
	 * Fetches and returns metadata for a resource
	 *
	 * @param F3_FLOW3_Property_DataType_URI $URI
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function extractResourceMetadata(F3_FLOW3_Property_DataType_URI $URI) {
		$metadata = array();

		$metadata['URI'] = $URI;
		$metadata['path'] = $this->publicResourcePath . $URI->getHost() . '/Resources' . dirname($URI->getPath());
		$metadata['name'] = basename($URI->getPath());
		$metadata['mimeType'] = F3_FLOW3_Utility_FileTypes::mimeTypeFromFilename($URI->getPath());
		$metadata['mediaType'] = F3_FLOW3_Utility_FileTypes::mediaTypeFromFilename($URI->getPath());

		return $metadata;
	}
}

?>