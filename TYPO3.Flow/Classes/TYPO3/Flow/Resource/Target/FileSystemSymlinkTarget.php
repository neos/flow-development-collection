<?php
namespace TYPO3\Flow\Resource\Target;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Collection;
use TYPO3\Flow\Resource\Storage\PackageStorage;
use TYPO3\Flow\Utility\Files;

/**
 * A target which publishes resources by creating symlinks.
 */
class FileSystemSymlinkTarget extends FileSystemTarget {


	/**
	 * Publishes the whole collection to this target
	 *
	 * @param \TYPO3\Flow\Resource\Collection $collection The collection to publish
	 * @return void
	 */
	public function publishCollection(Collection $collection) {
		$storage = $collection->getStorage();
		if($storage instanceof PackageStorage) {
			foreach ($storage->getPublicResourcePaths() as $packageKey => $path) {
				$this->publishDirectory($path, $packageKey);
			}
		} else {
			parent::publishCollection($collection);
		}
	}

	/**
	 * Publishes the given source stream to this target, with the given relative path.
	 *
	 * @param resource $sourceStream Stream of the source to publish
	 * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
	 * @throws Exception
	 * @throws \TYPO3\Flow\Utility\Exception
	 */
	protected function publishFile($sourceStream, $relativeTargetPathAndFilename) {
		$streamMetaData = stream_get_meta_data($sourceStream);

		if ($streamMetaData['wrapper_type'] !== 'plainfile' || $streamMetaData['stream_type'] !== 'STDIO') {
			throw new Exception(sprintf('Could not publish stream "%s" into resource publishing target "%s" because the source is not a local file.', $streamMetaData['uri'], $this->name), 1416242392);
		}

		$sourcePathAndFilename = $streamMetaData['uri'];
		$targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

		if (@stat($sourcePathAndFilename) === FALSE) {
			throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file is not accessible (file stat failed).', $sourcePathAndFilename, $this->name), 1415716366);
		}

		if (!file_exists(dirname($targetPathAndFilename))) {
			Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		try {
			$temporaryTargetPathAndFilename = uniqid($targetPathAndFilename . '.') . '.tmp';
			symlink($sourcePathAndFilename, $temporaryTargetPathAndFilename);
			$result = rename($temporaryTargetPathAndFilename, $targetPathAndFilename);
		} catch (\Exception $exception) {
			$result = FALSE;
		}
		if ($result === FALSE) {
			throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source file could not be symlinked at target location.', $sourcePathAndFilename, $this->name), 1415716368, (isset($exception) ? $exception : NULL));
		}

		$this->systemLogger->log(sprintf('FileSystemSymlinkTarget: Published file. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename), LOG_DEBUG);
	}

	/**
	 * Publishes the specified directory to this target, with the given relative path.
	 *
	 * @param string $sourcePath Absolute path to the source directory
	 * @param string $relativeTargetPathAndFilename relative path and filename in the target directory
	 * @throws Exception
	 * @return void
	 */
	protected function publishDirectory($sourcePath, $relativeTargetPathAndFilename) {
		$targetPathAndFilename = $this->path . $relativeTargetPathAndFilename;

		if (@stat($sourcePath) === FALSE) {
			throw new Exception(sprintf('Could not publish directory "%s" into resource publishing target "%s" because the source is not accessible (file stat failed).', $sourcePath, $this->name), 1416244512);
		}

		if (!file_exists(dirname($targetPathAndFilename))) {
			Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}

		try {
			$temporaryTargetPathAndFilename = uniqid($targetPathAndFilename . '.') . '.tmp';
			symlink($sourcePath, $temporaryTargetPathAndFilename);
			$result = rename($temporaryTargetPathAndFilename, $targetPathAndFilename);
		} catch (\Exception $exception) {
			$result = FALSE;
		}
		if ($result === FALSE) {
			throw new Exception(sprintf('Could not publish "%s" into resource publishing target "%s" because the source directory could not be symlinked at target location.', $sourcePath, $this->name), 1416244515, (isset($exception) ? $exception : NULL));
		}

		$this->systemLogger->log(sprintf('FileSystemSymlinkTarget: Published directory. (target: %s, file: %s)', $this->name, $relativeTargetPathAndFilename), LOG_DEBUG);
	}

}

