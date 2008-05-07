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
 * @package FLOW3F3_FLOW3_Property_DataType_URI
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
 * @scope singleton
 */
class F3_FLOW3_Resource_Publisher {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Configuration_Container The FLOW3 base configuration
	 */
	protected $configuration;

	/**
	 * @var string The base path for the mirrored public assets
	 */
	protected $publicResourcePath = NULL;

	/**
	 * @var F3_FLOW3_Cache_VariableCache The cache used for storing metadata about resources
	 */
	protected $resourceMetadataCache;

	/**
	 * @var integer One of the CACHE_STRATEGY constants defined in F3_FLOW3_Resource_Manager
	 */
	protected $cacheStrategy = F3_FLOW3_Resource_Manager::CACHE_STRATEGY_NONE;

	/**
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectComponentManager(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Sets the path to the asset mirror directory and makes sure it exists
	 *
	 * @param string $path
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeMirrorDirectory($path) {
		$this->publicResourcePath = $path;
		if (!is_writable($this->publicResourcePath)) {
			F3_FLOW3_Utility_Files::createDirectoryRecursively($this->publicResourcePath);
		}
		if (!is_dir($this->publicResourcePath)) throw new F3_FLOW3_Resource_Exception_FileDoesNotExist('The directory "' . $this->publicResourcePath . '" does not exist.', 1207124538);
		if (!is_writable($this->publicResourcePath)) throw new F3_FLOW3_Resource_Exception('The directory "' . $this->publicResourcePath . '" is not writable.', 1207124546);
	}

	/**
	 * Sets the cache used for storing meta data about resources
	 *
	 * @param F3_FLOW3_Cache_VariableCache $metadataCache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMetadataCache(F3_FLOW3_Cache_VariableCache $metadataCache) {
		$this->resourceMetadataCache = $metadataCache;
	}

	/**
	 * Sets the cache strategy to use for resource files
	 *
	 * @param integer $strategy One of the CACHE_STRATEGY constants from F3_FLOW3_Resource_Manager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setCacheStrategy($strategy) {
		$this->cacheStrategy = $strategy;
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
			$metadata = $this->extractResourceMetadata($URI);
			$this->resourceMetadataCache->save($identifier, $metadata);
		}
		return $metadata;
	}

	/**
	 * Publishes all public resources of a package
	 *
	 * @param string $packageName
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mirrorPublicPackageResources($packageName) {
		if($this->cacheStrategy === F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE && $this->resourceMetadataCache->has($packageName . 'IsMirrored')) {
			return;
		} elseif($this->cacheStrategy === F3_FLOW3_Resource_Manager::CACHE_STRATEGY_PACKAGE) {
			$this->resourceMetadataCache->save($packageName . 'IsMirrored', TRUE);
		}

		$sourcePath = FLOW3_PATH_PACKAGES . $packageName . '/Resources/Public/';
		if(!is_dir($sourcePath)) return;

		$destinationPath = $this->publicResourcePath . $packageName . '/Public/';
		$resourcesDirectoryIterator = new RecursiveDirectoryIterator($sourcePath);
		$resourceFilenames = F3_FLOW3_Utility_Files::readDirectoryRecursively($sourcePath);

		foreach($resourceFilenames as $file) {
			$relativeFile = str_replace($sourcePath, '', $file);
			$sourceMTime = filemtime($file);
			if($this->cacheStrategy === F3_FLOW3_Resource_Manager::CACHE_STRATEGY_FILE && file_exists($destinationPath . $relativeFile)) {
				$destMTime = filemtime($destinationPath . $relativeFile);
				if($sourceMTime === $destMTime) continue;
			}

			$URI = $this->createURI('file://' . $packageName . '/Public/' . $relativeFile);
			$metadata = $this->extractResourceMetadata($URI);

			F3_FLOW3_Utility_Files::createDirectoryRecursively($destinationPath . dirname($relativeFile));
			if($metadata['mimeType'] == 'text/html') {
				$HTML = F3_FLOW3_Resource_Processor::adjustRelativePathsInHTML(file_get_contents($file), 'Resources/' . $packageName . '/Public/' . dirname($relativeFile) . '/');
				file_put_contents($destinationPath . $relativeFile, $HTML);
			} else {
				copy($file, $destinationPath . $relativeFile);
			}
			if(!file_exists($destinationPath . $relativeFile)) {
				throw new F3_FLOW3_Resource_Exception('The resource "' . $relativeFile . '" could not be mirrored.', 1207255453);
			}
			touch($destinationPath . $relativeFile, $sourceMTime);

			$this->resourceMetadataCache->save(md5((string)$URI), $metadata);
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
		$metadata = array(
			'URI' => $URI,
			'path' => $this->publicResourcePath . $URI->getHost() . dirname($URI->getPath()),
			'name' => basename($URI->getPath()),
			'mimeType' => F3_FLOW3_Utility_FileTypes::mimeTypeFromFilename($URI->getPath()),
			'mediaType' => F3_FLOW3_Utility_FileTypes::mediaTypeFromFilename($URI->getPath()),
		);

		return $metadata;
	}

	/**
	 * Returns a new URI object
	 *
	 * @param string $URIString
	 * @return F3_FLOW3_Property_DataType_URI
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createURI($URIString) {
		return new F3_FLOW3_Property_DataType_URI($URIString);
	}
}

?>