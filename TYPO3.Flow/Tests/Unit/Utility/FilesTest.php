<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

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
use TYPO3\Flow\Utility\Files;

/**
 * Testcase for the Utility Files class
 */
class FilesTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $temporaryDirectory;

	public function setUp() {
		vfsStream::setup('Foo');

		$intendedTemporaryDirectory = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__);
		if (!file_exists($intendedTemporaryDirectory)) {
			mkdir($intendedTemporaryDirectory);
		}
		$this->temporaryDirectory = realpath($intendedTemporaryDirectory);
	}

	public function tearDown() {
		Files::removeDirectoryRecursively($this->temporaryDirectory);
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithoutSlashes() {
		$path = 'foobar';
		$this->assertEquals('foobar', Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithForwardSlashes() {
		$path = 'foo/bar/test/';
		$this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithBackwardSlashes() {
		$path = 'foo\\bar\\test\\';
		$this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithForwardAndBackwardSlashes() {
		$path = 'foo/bar\\test/';
		$this->assertEquals('foo/bar/test/', Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForEmptyPath() {
		$this->assertEquals('', Files::concatenatePaths(array()));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForOnePath() {
		$this->assertEquals('foo', Files::concatenatePaths(array('foo')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForTwoPath() {
		$this->assertEquals('foo/bar', Files::concatenatePaths(array('foo', 'bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithLeadingSlash() {
		$this->assertEquals('/foo/bar', Files::concatenatePaths(array('/foo', 'bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithTrailingSlash() {
		$this->assertEquals('foo/bar', Files::concatenatePaths(array('foo', 'bar/')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForPathsWithLeadingAndTrailingSlash() {
		$this->assertEquals('/foo/bar/bar/foo', Files::concatenatePaths(array('/foo/bar/', '/bar/foo/')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForBrokenPaths() {
		$this->assertEquals('/foo/bar/bar', Files::concatenatePaths(array('\\foo/bar\\', '\\bar')));
	}

	/**
	 * @test
	 */
	public function concatenatePathsWorksForEmptyPathArrayElements() {
		$this->assertEquals('foo/bar', Files::concatenatePaths(array('foo', '', 'bar')));
	}

	/**
	 * @test
	 */
	public function getUnixStylePathWorksForPathWithDriveLetterAndBackwardSlashes() {
		$path = 'c:\\foo\\bar\\test\\';
		$this->assertEquals('c:/foo/bar/test/', Files::getUnixStylePath($path));
	}

	/**
	 */
	public function pathsWithProtocol() {
		return array(
			array('file:///foo\\bar', 'file:///foo/bar'),
			array('vfs:///foo\\bar', 'vfs:///foo/bar'),
			array('phar:///foo\\bar', 'phar:///foo/bar')
		);
	}

	/**
	 * @test
	 * @dataProvider pathsWithProtocol
	 */
	public function getUnixStylePathWorksForPathWithProtocol($path, $expected) {
		$this->assertEquals($expected, Files::getUnixStylePath($path));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForNonExistingFiles() {
		$this->assertFalse(Files::is_link('NonExistingPath'));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForExistingFileThatIsNoSymlink() {
		$targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
		file_put_contents($targetPathAndFilename, 'some data');
		$this->assertFalse(Files::is_link($targetPathAndFilename));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsTrueForExistingSymlink() {
		$targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
		file_put_contents($targetPathAndFilename, 'some data');
		$linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
		if (file_exists($linkPathAndFilename)) {
			@unlink($linkPathAndFilename);
		}
		symlink($targetPathAndFilename, $linkPathAndFilename);
		$this->assertTrue(Files::is_link($linkPathAndFilename));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForExistingDirectoryThatIsNoSymlink() {
		$targetPath = Files::concatenatePaths(array(dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory')) . '/';
		if (!is_dir($targetPath)) {
			Files::createDirectoryRecursively($targetPath);
		}
		$this->assertFalse(Files::is_link($targetPath));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsTrueForExistingSymlinkDirectory() {
		$targetPath = Files::concatenatePaths(array(dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory'));
		if (!is_dir($targetPath)) {
			Files::createDirectoryRecursively($targetPath);
		}
		$linkPath = Files::concatenatePaths(array(dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectoryLink'));
		if (is_dir($linkPath)) {
			Files::removeDirectoryRecursively($linkPath);
		}
		symlink($targetPath, $linkPath);
		$this->assertTrue(Files::is_link($linkPath));
	}

	/**
	 * @test
	 */
	public function is_linkReturnsFalseForStreamWrapperPaths() {
		$targetPath = 'vfs://Foo/Bar';
		if (!is_dir($targetPath)) {
			Files::createDirectoryRecursively($targetPath);
		}
		$this->assertFalse(Files::is_link($targetPath));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Utility\Exception
	 */
	public function emptyDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist() {
		Files::emptyDirectoryRecursively('NonExistingPath');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Utility\Exception
	 */
	public function removeDirectoryRecursivelyThrowsExceptionIfSpecifiedPathDoesNotExist() {
		Files::removeDirectoryRecursively('NonExistingPath');
	}

	/**
	 * @test
	 */
	public function removeEmptyDirectoriesOnPathRemovesAllDirectoriesOnPathIfTheyAreEmpty() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
		$this->assertFalse(file_exists('vfs://Foo'));
	}

	/**
	 * @test
	 */
	public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesWhichAreEmpty() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
		$this->assertTrue(file_exists('vfs://Foo/Bar/someFile.txt'));
		$this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
	}

	/**
	 * @test
	 */
	public function removeEmptyDirectoriesOnPathDoesNotRemoveAnythingIfTopLevelPathContainsFile() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		file_put_contents('vfs://Foo/Bar/Baz/Quux/someFile.txt', 'x');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
		$this->assertTrue(file_exists('vfs://Foo/Bar/Baz/Quux/someFile.txt'));
	}

	/**
	 * @test
	 */
	public function removeEmptyDirectoriesOnPathAlsoRemovesOSXFinderFilesIfNecessary() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		file_put_contents('vfs://Foo/Bar/someFile.txt', 'x');
		file_put_contents('vfs://Foo/Bar/Baz/.DS_Store', 'x');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux');
		$this->assertTrue(file_exists('vfs://Foo/Bar/someFile.txt'));
		$this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
	}

	/**
	 * @test
	 */
	public function removeEmptyDirectoriesOnPathRemovesOnlyDirectoriesBelowTheGivenBasePath() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar');
		$this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
		$this->assertTrue(file_exists('vfs://Foo/Bar'));

		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Foo/Bar/');
		$this->assertFalse(file_exists('vfs://Foo/Bar/Baz'));
		$this->assertTrue(file_exists('vfs://Foo/Bar'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Utility\Exception
	 */
	public function removeEmptyDirectoriesOnPathThrowsExceptionIfBasePathIsNotParentOfPath() {
		Files::createDirectoryRecursively('vfs://Foo/Bar/Baz/Quux');
		Files::removeEmptyDirectoriesOnPath('vfs://Foo/Bar/Baz/Quux', 'vfs://Other/Bar');
	}

	/**
	 * @test
	 */
	public function unlinkProperlyRemovesSymlinksPointingToFiles() {
		$targetPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestFile');
		file_put_contents($targetPathAndFilename, 'some data');
		$linkPathAndFilename = tempnam($this->temporaryDirectory, 'FlowFilesTestLink');
		if (file_exists($linkPathAndFilename)) {
			@unlink($linkPathAndFilename);
		}
		symlink($targetPathAndFilename, $linkPathAndFilename);
		$this->assertTrue(Files::unlink($linkPathAndFilename));
		$this->assertTrue(file_exists($targetPathAndFilename));
		$this->assertFalse(file_exists($linkPathAndFilename));
	}

	/**
	 * @test
	 */
	public function unlinkProperlyRemovesSymlinksPointingToDirectories() {
		$targetPath = Files::concatenatePaths(array(dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectory'));
		if (!is_dir($targetPath)) {
			Files::createDirectoryRecursively($targetPath);
		}
		$linkPath = Files::concatenatePaths(array(dirname(tempnam($this->temporaryDirectory, '')), 'FlowFilesTestDirectoryLink'));
		if (is_dir($linkPath)) {
			Files::removeDirectoryRecursively($linkPath);
		}
		symlink($targetPath, $linkPath);
		$this->assertTrue(Files::unlink($linkPath));
		$this->assertTrue(file_exists($targetPath));
		$this->assertFalse(file_exists($linkPath));
	}

	/**
	 * @test
	 * @outputBuffering enabled
	 *     ... because the chmod call in ResourceManager emits a warning making this fail in strict mode
	 */
	public function unlinkReturnsFalseIfSpecifiedPathDoesNotExist() {
		$this->assertFalse(Files::unlink('NonExistingPath'));
	}

	/**
	 * @test
	 */
	public function copyDirectoryRecursivelyCreatesTargetAsExpected() {
		Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
		file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

		Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');

		$this->assertTrue(is_dir('vfs://Foo/target/bar/baz'));
		$this->assertTrue(is_file('vfs://Foo/target/bar/baz/file.txt'));
		$this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
	}

	/**
	 * @test
	 */
	public function copyDirectoryRecursivelyCopiesDotFilesIfRequested() {
		Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
		file_put_contents('vfs://Foo/source/bar/baz/.file.txt', 'source content');

		Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', FALSE, TRUE);

		$this->assertTrue(is_dir('vfs://Foo/target/bar/baz'));
		$this->assertTrue(is_file('vfs://Foo/target/bar/baz/.file.txt'));
		$this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/.file.txt'));
	}

	/**
	 * @test
	 */
	public function copyDirectoryRecursivelyOverwritesTargetFiles() {
		Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
		file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

		Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
		file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

		Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target');
		$this->assertEquals('source content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
	}

	/**
	 * @test
	 */
	public function copyDirectoryRecursivelyKeepsExistingTargetFilesIfRequested() {
		Files::createDirectoryRecursively('vfs://Foo/source/bar/baz');
		file_put_contents('vfs://Foo/source/bar/baz/file.txt', 'source content');

		Files::createDirectoryRecursively('vfs://Foo/target/bar/baz');
		file_put_contents('vfs://Foo/target/bar/baz/file.txt', 'target content');

		Files::copyDirectoryRecursively('vfs://Foo/source', 'vfs://Foo/target', TRUE);
		$this->assertEquals('target content', file_get_contents('vfs://Foo/target/bar/baz/file.txt'));
	}
}
