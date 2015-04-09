<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Cache\Backend\SimpleFileBackend;
use TYPO3\Flow\Cache\Frontend\FrontendInterface;
use TYPO3\Flow\Cache\Frontend\PhpFrontend;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\Environment;

/**
 * Test case for the SimpleFileBackend
 */
class SimpleFileBackendTest extends UnitTestCase {

	/**
	 * @var ApplicationContext|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockApplicationContext;

	/**
	 * @var Environment|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockEnvironment;

	/**
	 * @var FrontendInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockCacheFrontend;

	/**
	 * @return void
	 */
	public function setUp() {
		vfsStream::setup('Temporary/Directory/');

		$this->mockApplicationContext = $this->getMockBuilder('TYPO3\Flow\Core\ApplicationContext')->disableOriginalConstructor()->getMock();

		$this->mockEnvironment = $this->getMockBuilder('TYPO3\Flow\Utility\Environment')->disableOriginalConstructor()->getMock();
		$this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Temporary/Directory/'));

		$this->mockCacheFrontend = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\FrontendInterface')->getMock();
	}

	/**
	 * Convenience function to retrieve an instance of SimpleFileBackend with required dependencies
	 *
	 * @param array $options
	 * @return SimpleFileBackend
	 */
	protected function getSimpleFileBackend(array $options = array()) {
		$simpleFileBackend = new SimpleFileBackend($this->mockApplicationContext, $options);
		$this->inject($simpleFileBackend, 'environment', $this->mockEnvironment);
		return $simpleFileBackend;
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception
	 */
	public function setCacheThrowsExceptionOnNonWritableDirectory() {
		$this->mockEnvironment = $this->getMockBuilder('TYPO3\Flow\Utility\Environment')->disableOriginalConstructor()->getMock();
		$this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://non/existing/directory'));
		$this->getSimpleFileBackend()->setCache($this->mockCacheFrontend);
	}


	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception
	 */
	public function setCacheThrowsExceptionIfCachePathLengthExceedsMaximumPathLength() {
		$this->mockEnvironment = $this->getMockBuilder('TYPO3\Flow\Utility\Environment')->disableOriginalConstructor()->getMock();
		$this->mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(5));
		$this->mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Temporary/Directory/'));
		$this->getSimpleFileBackend()->setCache($this->mockCacheFrontend);
	}

	/**
	 * @test
	 */
	public function setCacheDirectoryAllowsToSetTheCurrentCacheDirectory() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

		// We need to create the directory here because vfs doesn't support touch() which is used by
		// createDirectoryRecursively() in the setCache method.
		mkdir ('vfs://Temporary/Directory/Cache');
		mkdir ('vfs://Temporary/Directory/OtherDirectory');

		$simpleFileBackend = $this->getSimpleFileBackend(['cacheDirectory' => 'vfs://Temporary/Directory/OtherDirectory']);
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$this->assertEquals('vfs://Temporary/Directory/OtherDirectory/', $simpleFileBackend->getCacheDirectory());
	}

	/**
	 * @test
	 */
	public function getCacheDirectoryReturnsTheCurrentCacheDirectory() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

		// We need to create the directory here because vfs doesn't support touch() which is used by
		// createDirectoryRecursively() in the setCache method.
		mkdir ('vfs://Temporary/Directory/Cache');

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$this->assertEquals('vfs://Temporary/Directory/Cache/Data/SomeCache/', $simpleFileBackend->getCacheDirectory());
	}

	/**
	 * @test
	 */
	public function aDedicatedCacheDirectoryIsUsedForCodeCaches() {
		/** @var PhpFrontend|\PHPUnit_Framework_MockObject_MockObject $mockPhpCacheFrontend */
		$mockPhpCacheFrontend = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\PhpFrontend')->disableOriginalConstructor()->getMock();
		$mockPhpCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomePhpCache'));

		$mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(1024));

		// We need to create the directory here because vfs doesn't support touch() which is used by
		// createDirectoryRecursively() in the setCache method.
		mkdir ('vfs://Temporary/Directory/Cache');

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($mockPhpCacheFrontend);
		$this->assertEquals('vfs://Temporary/Directory/Cache/Code/SomePhpCache/', $simpleFileBackend->getCacheDirectory());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception\InvalidDataException
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->set('SomeIdentifier', array('not a string'));
	}

	/**
	 * @test
	 */
	public function setReallySavesToTheSpecifiedDirectory() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = uniqid('some data');
		$entryIdentifier = 'SimpleFileBackendTest';
		$pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, $data);

		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename);
		$this->assertEquals($data, $retrievedData);
	}

	/**
	 * @test
	 */
	public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = uniqid('some data');
		$data2 = uniqid('some other data');
		$entryIdentifier = 'SimpleFileBackendTest';
		$pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, $data1);
		$simpleFileBackend->set($entryIdentifier, $data2);

		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename);
		$this->assertEquals($data2, $retrievedData);
	}

	/**
	 * @test
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = uniqid('some data');
		$data2 = uniqid('some other data');
		$entryIdentifier = 'SimpleFileBackendTest';

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, $data1);
		$simpleFileBackend->set($entryIdentifier, $data2);

		$this->assertSame($data2, $simpleFileBackend->get($entryIdentifier));
	}

	/**
	 * @test
	 */
	public function getReturnsFalseForDeletedFiles() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$entryIdentifier = 'SimpleFileBackendTest';
		$pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, 'some data');

		unlink($pathAndFilename);

		$this->assertFalse($simpleFileBackend->get($entryIdentifier));
	}

	/**
	 * @test
	 */
	public function hasReturnsTrueIfAnEntryExists() {
		$entryIdentifier = 'SimpleFileBackendTest';

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, 'some data');

		$this->assertTrue($simpleFileBackend->has($entryIdentifier));
	}

	/**
	 * @test
	 */
	public function hasReturnsFalseIfAnEntryDoesNotExist() {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set('SomeEntryIdentifier', 'some data');

		$this->assertFalse($simpleFileBackend->has('SomeNonExistingEntryIdentifier'));
	}

	/**
	 * @test
	 */
	public function removeReallyRemovesACacheEntry() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$entryIdentifier = 'SimpleFileBackendTest';
		$pathAndFilename = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier;

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier, 'some data');

		$this->assertFileExists($pathAndFilename);
		$this->assertTrue($simpleFileBackend->has($entryIdentifier));

		$simpleFileBackend->remove($entryIdentifier);

		$this->assertFileNotExists($pathAndFilename);
		$this->assertFalse($simpleFileBackend->has($entryIdentifier));
	}

	/**
	 * @return array
	 */
	public function invalidEntryIdentifiers() {
		return array(
			'trailing slash' => array('/myIdentifer'),
			'trailing dot and slash' => array('./myIdentifer'),
			'trailing two dots and slash' => array('../myIdentifier'),
			'trailing with multiple dots and slashes' => array('.././../myIdentifier'),
			'slash in middle part' => array('my/Identifier'),
			'dot and slash in middle part' => array('my./Identifier'),
			'two dots and slash in middle part' => array('my../Identifier'),
			'multiple dots and slashes in middle part' => array('my.././../Identifier'),
			'pending slash' => array('myIdentifier/'),
			'pending dot and slash' => array('myIdentifier./'),
			'pending dots and slash' => array('myIdentifier../'),
			'pending multiple dots and slashes' => array('myIdentifier.././../'),
		);
	}

	/**
	 * @test
	 * @param string $identifier
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException \InvalidArgumentException
	 */
	public function setThrowsExceptionForInvalidIdentifier($identifier) {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->set($identifier, 'some data');
	}

	/**
	 * @test
	 * @param string $identifier
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException \InvalidArgumentException
	 */
	public function getThrowsExceptionForInvalidIdentifier($identifier) {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->get($identifier);
	}

	/**
	 * @test
	 * @param string $identifier
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException \InvalidArgumentException
	 */
	public function hasThrowsExceptionForInvalidIdentifier($identifier) {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->has($identifier);
	}

	/**
	 * @test
	 * @param string $identifier
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException \InvalidArgumentException
	 */
	public function removeThrowsExceptionForInvalidIdentifier($identifier) {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->remove($identifier);
	}

	/**
	 * @test
	 * @param string $identifier
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException \InvalidArgumentException
	 */
	public function requireOnceThrowsExceptionForInvalidIdentifier($identifier) {
		$simpleFileBackend = $this->getSimpleFileBackend();

		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->requireOnce($identifier);
	}

	/**
	 * @test
	 */
	public function requireOnceIncludesAndReturnsResultOfIncludedPhpFile() {
		$entryIdentifier = 'SomeValidPhpEntry';

		$simpleFileBackend = $this->getSimpleFileBackend();

		$data = '<?php return "foo";';
		$simpleFileBackend->set($entryIdentifier, $data);

		$loadedData = $simpleFileBackend->requireOnce($entryIdentifier);
		$this->assertEquals('foo', $loadedData);
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function requireOnceDoesNotSwallowExceptionsOfTheIncludedFile() {
		$entryIdentifier = 'SomePhpEntryWithException';

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->set($entryIdentifier, '<?php throw new \Exception(); ?>');
		$simpleFileBackend->requireOnce($entryIdentifier);
	}

	/**
	 * @test
	 * @expectedException \PHPUnit_Framework_Error_Warning
	 */
	public function requireOnceDoesNotSwallowPhpWarningsOfTheIncludedFile() {
		$entryIdentifier = 'SomePhpEntryWithPhpWarning';

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->set($entryIdentifier, '<?php trigger_error("Warning!", E_WARNING); ?>');
		$simpleFileBackend->requireOnce($entryIdentifier);
	}

	/**
	 * @test
	 * @expectedException \PHPUnit_Framework_Error_Notice
	 */
	public function requireOnceDoesNotSwallowPhpNoticesOfTheIncludedFile() {
		$entryIdentifier = 'SomePhpEntryWithPhpNotice';

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->set($entryIdentifier, '<?php $undefined ++; ?>');
		$simpleFileBackend->requireOnce($entryIdentifier);
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		$this->mockCacheFrontend->expects($this->any())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$entryIdentifier1 = 'SimpleFileBackendTest1';
		$pathAndFilename1 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier1;
		$entryIdentifier2 = 'SimpleFileBackendTest2';
		$pathAndFilename2 = 'vfs://Temporary/Directory/Cache/Data/UnitTestCache/' . $entryIdentifier2;

		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);
		$simpleFileBackend->set($entryIdentifier1, 'some data');
		$simpleFileBackend->set($entryIdentifier2, 'some more data');

		$this->assertFileExists($pathAndFilename1);
		$this->assertFileExists($pathAndFilename2);
		$this->assertTrue($simpleFileBackend->has($entryIdentifier1));
		$this->assertTrue($simpleFileBackend->has($entryIdentifier2));

		$simpleFileBackend->flush();

		$this->assertFileNotExists($pathAndFilename1);
		$this->assertFalse($simpleFileBackend->has($entryIdentifier1));
		$this->assertFileNotExists($pathAndFilename2);
		$this->assertFalse($simpleFileBackend->has($entryIdentifier2));
	}

	/**
	 * @test
	 */
	public function backendAllowsForIteratingOverEntries() {
		$simpleFileBackend = $this->getSimpleFileBackend();
		$simpleFileBackend->setCache($this->mockCacheFrontend);

		for ($i = 0; $i < 100; $i++) {
			$entryIdentifier = sprintf('entry-%s', $i);
			$data = 'some data ' . $i;
			$simpleFileBackend->set($entryIdentifier, $data);
		}

		$entries = array();
		foreach ($simpleFileBackend as $entryIdentifier => $data) {
			$entries[$entryIdentifier] = $data;
		}
		natsort($entries);
		$i = 0;
		foreach ($entries as $entryIdentifier => $data) {
			$this->assertEquals(sprintf('entry-%s', $i), $entryIdentifier);
			$this->assertEquals('some data ' . $i, $data);
			$i++;
		}
		$this->assertEquals(100, $i);
	}

}
