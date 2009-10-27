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
 * Support functions for handling assets
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Publisher {

	/**
	 * Constants reflecting the file caching strategies
	 * @var string
	 * @api
	 */
	const CACHE_STRATEGY_NONE = 'none';
	const CACHE_STRATEGY_PACKAGE = 'package';
	const CACHE_STRATEGY_FILE = 'file';

	/**
	 * Constants reflecting the mirror mode settings
	 * @var string
	 * @api
	 */
	const MIRROR_MODE_COPY = 'copy';
	const MIRROR_MODE_LINK = 'link';

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * The (absolute) base path for the mirrored public assets
	 * @var string
	 */
	protected $mirrorDirectory;

	/**
	 * The cache used for storing metadata about resources
	 * @var \F3\FLOW3\Cache\Frontend\StringFrontend
	 */
	protected $mirrorStatusCache;

	/**
	 * One of the CACHE_STRATEGY constants
	 * @var integer
	 */
	protected $mirrorStrategy = self::CACHE_STRATEGY_NONE;

	/**
	 * One of the MIRROR_MODE_* constants
	 * @var string
	 */
	protected $mirrorMode = self::MIRROR_MODE_COPY;

	/**
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\ManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Sets the path to the asset mirror directory and makes sure it exists
	 *
	 * @param string $path
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMirrorDirectory($path) {
		$this->mirrorDirectory = FLOW3_PATH_WEB . $path;
		if (!is_writable($this->mirrorDirectory)) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($this->mirrorDirectory);
		}
		if (!is_dir($this->mirrorDirectory)) throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->mirrorDirectory . '" does not exist.', 1207124538);
		if (!is_writable($this->mirrorDirectory)) throw new \F3\FLOW3\Resource\Exception('The directory "' . $this->mirrorDirectory . '" is not writable.', 1207124546);
	}

	/**
	 * Returns the path to the asset mirror directory.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getMirrorDirectory() {
		return $this->mirrorDirectory;
	}

	/**
	 * Sets the cache used for storing resources status
	 *
	 * @param \F3\FLOW3\Cache\Frontend\StringFrontend $statusCache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMirrorStatusCache(\F3\FLOW3\Cache\Frontend\StringFrontend $statusCache) {
		$this->mirrorStatusCache = $statusCache;
	}

	/**
	 * Sets the cache strategy to use for resource files
	 *
	 * @param integer $strategy One of the CACHE_STRATEGY_* constants
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMirrorStrategy($strategy) {
		$this->mirrorStrategy = $strategy;
	}

	/**
	 * Sets the cache strategy to use for resource files
	 *
	 * @param integer $strategy One of the MIRROR_MODE_* constants
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setMirrorMode($mirrorMode) {
		$this->mirrorMode = $mirrorMode;
	}

	/**
	 * Recursively publishes all resources found in the specified source directory
	 * to the given destination.
	 *
	 * @param string $sourcePath Path containing the resources to publish
	 * @param string $relativeTargetPath Path relative to the public resources directory where the given resources are mirrored to
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mirrorResources($sourcePath, $relativeTargetPath) {
		if (!is_dir($sourcePath)) {
			return;
		}

		$cacheEntryIdentifier = md5($sourcePath);
		if ($this->mirrorStrategy === self::CACHE_STRATEGY_PACKAGE && $this->mirrorStatusCache->has($cacheEntryIdentifier)) {
			return;
		} elseif ($this->mirrorStrategy === self::CACHE_STRATEGY_PACKAGE) {
			$this->mirrorStatusCache->set($cacheEntryIdentifier, '');
		}

		$destinationPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->mirrorDirectory, $relativeTargetPath));

		foreach (\F3\FLOW3\Utility\Files::readDirectoryRecursively($sourcePath) as $sourcePathAndFileName) {
			if (substr(strtolower($sourcePathAndFileName), -4, 4) === '.php') continue;

			$targetPathAndFileName = \F3\FLOW3\Utility\Files::concatenatePaths(array($destinationPath, str_replace($sourcePath, '', $sourcePathAndFileName)));
			$sourceMTime = filemtime($sourcePathAndFileName);
			if ($this->mirrorStrategy === self::CACHE_STRATEGY_FILE && file_exists($targetPathAndFileName)) {
				$destMTime = filemtime($targetPathAndFileName);
				if ($sourceMTime <= $destMTime) continue;
			}

			\F3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFileName));
			switch ($this->mirrorMode) {
				case self::MIRROR_MODE_COPY:
					copy($sourcePathAndFileName, $targetPathAndFileName);
					touch($targetPathAndFileName, $sourceMTime);
				break;
				case self::MIRROR_MODE_LINK:
					if (file_exists($targetPathAndFileName)) {
						if (is_link($targetPathAndFileName) && (readlink($targetPathAndFileName) === $sourcePathAndFileName)) {
							break;
						}
						unlink($targetPathAndFileName);
						symlink($sourcePathAndFileName, $targetPathAndFileName);
					} else {
						symlink($sourcePathAndFileName, $targetPathAndFileName);
					}
				break;
				default:
					throw new \RuntimeException('An invalid mirror mode (' . $this->mirrorMode . ') has been configured.', 1256133400);
			}
			if (!file_exists($targetPathAndFileName)) {
				throw new \F3\FLOW3\Resource\Exception('The resource "' . str_replace($sourcePath, '', $sourcePathAndFileName) . '" could not be mirrored.', 1207255453);
			}
		}
	}

}

?>