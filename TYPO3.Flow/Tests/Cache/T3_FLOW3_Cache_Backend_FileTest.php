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
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the cache to file backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Cache_Backend_FileTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPrototype() {
		$backend1 = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File');
		$backend2 = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File');
		$this->assertNotSame($backend1, $backend2, 'File Backend seems to be singleton!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFilesDirectoryThrowsExceptionOnNonWritableDirectory() {
		switch (PHP_OS) {
			case 'Darwin' :
				$directoryName = '/private';
				break;
			case 'Linux' :
				$directoryName = '/sbin';
				break;
			default :
				throw new PHPUnit_Framework_IncompleteTestError('Didn\'t know how a non-writable directory for this platform.');
		}
		$backend = new T3_FLOW3_Cache_Backend_File;
		try {
			$backend->setFilesDirectory($directoryName);
			$this->fail('setFilesDirectory() to non-writable directory did not result in an exception.');
		} catch (T3_FLOW3_Cache_Exception $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveReallySavesToTheSpecifiedDirectory() {
		$environment = $this->componentManager->getComponent('T3_FLOW3_Utility_Environment');
		$directoryName = $environment->getPathToTemporaryDirectory() . '/FLOW3';
		$identifier = 'test-savereallysavestothespecifieddirectory';
		$data = 'some data' . microtime();
		$dataHash = sha1($data);

		$backend = new T3_FLOW3_Cache_Backend_File;
		$backend->setFilesDirectory($directoryName);
		$backend->save($data, $identifier);

		$pattern = $directoryName . '/' . $dataHash{0} . '/' . $dataHash{1} . '/????-??-?????;??;???_' . $identifier . '*.cachedata';
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound), 'filesFound was no array.');

		$retrievedData = file_get_contents(array_pop($filesFound));
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
		T3_FLOW3_Utility_Files::removeDirectoryRecursively($directoryName);
	}
}
?>