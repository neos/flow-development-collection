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
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 */

/**
 * Support functions for handling assets
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Publisher {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var string The base path for the mirrored public assets
	 */
	protected $publicResourcePath = NULL;

	/**
	 * @var \F3\FLOW3\Cache\VariableCache The cache used for storing metadata about resources
	 */
	protected $resourceMetadataCache;

	/**
	 * @var \F3\FLOW3\Cache\StringCache The cache used for storing metadata about resources
	 */
	protected $resourceStatusCache;

	/**
	 * @var integer One of the CACHE_STRATEGY constants defined in \F3\FLOW3\Resource\Manager
	 */
	protected $cacheStrategy = \F3\FLOW3\Resource\Manager::CACHE_STRATEGY_NONE;

	/**
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
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
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->publicResourcePath);
		}
		if (!is_dir($this->publicResourcePath)) throw new \F3\FLOW3\Resource\Exception\FileDoesNotExist('The directory "' . $this->publicResourcePath . '" does not exist.', 1207124538);
		if (!is_writable($this->publicResourcePath)) throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->publicResourcePath . '" is not writable.', 1207124546);
	}

	/**
	 * Sets the cache used for storing meta data about resources
	 *
	 * @param \F3\FLOW3\Cache\VariableCache $metadataCache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMetadataCache(\F3\FLOW3\Cache\VariableCache $metadataCache) {
		$this->resourceMetadataCache = $metadataCache;
	}

	/**
	 * Sets the cache used for storing resources status
	 *
	 * @param \F3\FLOW3\Cache\StringCache $statusCache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setStatusCache(\F3\FLOW3\Cache\StringCache $statusCache) {
		$this->resourceStatusCache = $statusCache;
	}

	/**
	 * Sets the cache strategy to use for resource files
	 *
	 * @param integer $strategy One of the CACHE_STRATEGY constants from \F3\FLOW3\Resource\Manager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setCacheStrategy($strategy) {
		$this->cacheStrategy = $strategy;
	}

	/**
	 * Returns metadata for the resource identified by URI
	 *
	 * @param \F3\FLOW3\Property\DataType\URI $URI
	 * @return unknown
	 */
	public function getMetadata(\F3\FLOW3\Property\DataType\URI $URI) {
		$metadata = array();
		$identifier = md5((string)$URI);
		if ($this->resourceMetadataCache->has($identifier)) {
			$metadata = $this->resourceMetadataCache->get($identifier);
		} else {
			$metadata = $this->extractResourceMetadata($URI);
			$this->resourceMetadataCache->set($identifier, $metadata);
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
		if ($this->cacheStrategy === \F3\FLOW3\Resource\Manager::CACHE_STRATEGY_PACKAGE && $this->resourceStatusCache->has($packageName)) {
			return;
		} elseif ($this->cacheStrategy === \F3\FLOW3\Resource\Manager::CACHE_STRATEGY_PACKAGE) {
			$this->resourceStatusCache->set($packageName, '');
		}

		$sourcePath = FLOW3_PATH_PACKAGES . $packageName . '/Resources/Public/';
		if (!is_dir($sourcePath)) return;

		$destinationPath = $this->publicResourcePath . $packageName . '/Public/';
		$resourceFilenames = \F3\FLOW3\Utility\Files::readDirectoryRecursively($sourcePath);

		foreach ($resourceFilenames as $file) {
			$relativeFile = str_replace($sourcePath, '', $file);
			$sourceMTime = filemtime($file);
			if ($this->cacheStrategy === \F3\FLOW3\Resource\Manager::CACHE_STRATEGY_FILE && file_exists($destinationPath . $relativeFile)) {
				$destMTime = filemtime($destinationPath . $relativeFile);
				if ($sourceMTime === $destMTime) continue;
			}

			$URI = $this->createURI('file://' . $packageName . '/Public/' . $relativeFile);
			$metadata = $this->extractResourceMetadata($URI);

			\F3\FLOW3\Utility\Files::createDirectoryRecursively($destinationPath . dirname($relativeFile));
			if ($metadata['mimeType'] == 'text/html') {
				$HTML = \F3\FLOW3\Resource\Processor::prefixRelativePathsInHTML(file_get_contents($file), 'Resources/Web/' . $packageName . '/Public/' . dirname($relativeFile) . '/');
				file_put_contents($destinationPath . $relativeFile, $HTML);
			} else {
				copy($file, $destinationPath . $relativeFile);
			}
			if (!file_exists($destinationPath . $relativeFile)) {
				throw new \F3\FLOW3\Resource\Exception('The resource "' . $relativeFile . '" could not be mirrored.', 1207255453);
			}
			touch($destinationPath . $relativeFile, $sourceMTime);

			$this->resourceMetadataCache->set(md5((string)$URI), $metadata);
		}
	}

	/**
	 * Fetches and returns metadata for a resource
	 *
	 * @param \F3\FLOW3\Property\DataType\URI $URI
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function extractResourceMetadata(\F3\FLOW3\Property\DataType\URI $URI) {
		$explodedPath = explode('/',dirname($URI->getPath()));
		if ($explodedPath[1] == 'Public') {
			$metadata = array(
				'URI' => $URI,
				'path' => $this->publicResourcePath . $URI->getHost() . dirname($URI->getPath()),
				'name' => basename($URI->getPath()),
				'mimeType' => \F3\FLOW3\Utility\FileTypes::mimeTypeFromFilename($URI->getPath()),
				'mediaType' => \F3\FLOW3\Utility\FileTypes::mediaTypeFromFilename($URI->getPath()),
			);
		} else {
			$metadata = array(
				'URI' => $URI,
				'path' => FLOW3_PATH_PACKAGES . $URI->getHost() . '/Resources' . dirname($URI->getPath()),
				'name' => basename($URI->getPath()),
				'mimeType' => \F3\FLOW3\Utility\FileTypes::mimeTypeFromFilename($URI->getPath()),
				'mediaType' => \F3\FLOW3\Utility\FileTypes::mediaTypeFromFilename($URI->getPath()),
			);
		}
		return $metadata;
	}

	/**
	 * Returns a new URI object
	 *
	 * @param string $URIString
	 * @return \F3\FLOW3\Property\DataType\URI
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function createURI($URIString) {
		return new \F3\FLOW3\Property\DataType\URI($URIString);
	}
}

?>