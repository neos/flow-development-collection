<?php
namespace TYPO3\Flow\Configuration\Source;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a configuration source
 *
 */
interface SourceInterface {

	/**
	 * Loads the specified configuration file and returns its content in a
	 * configuration container
	 *
	 * @param string $pathAndFilename Full path and filename of the file to load, excluding the dot and file extension
	 * @return array
	 * @throws \TYPO3\Flow\Configuration\Exception\NoSuchFileException if the specified file does not exist
	 */
	public function load($pathAndFilename);

	/**
	 * Save the specified configuration container to the given file
	 *
	 * @param string $pathAndFilename Full path and filename of the file to write to, excluding the dot and file extension
	 * @param array $configuration The configuration array to save
	 * @return void
	 */
	public function save($pathAndFilename, array $configuration);

}
?>