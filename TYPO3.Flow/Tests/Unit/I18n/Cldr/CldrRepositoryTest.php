<?php
namespace TYPO3\Flow\Tests\Unit\I18n\Cldr;

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

/**
 * Testcase for the CldrRepository
 *
 */
class CldrRepositoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Cldr\CldrRepository
	 */
	protected $repository;

	/**
	 * @var \TYPO3\Flow\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @return void
	 */
	public function setUp() {
		vfsStream::setup('Foo');

		$this->repository = $this->getAccessibleMock('TYPO3\Flow\I18n\Cldr\CldrRepository', array('dummy'));
		$this->repository->_set('cldrBasePath', 'vfs://Foo/');

		$this->dummyLocale = new \TYPO3\Flow\I18n\Locale('en');
	}

	/**
	 * @test
	 */
	public function modelIsReturnedCorrectlyForSingleFile() {
		file_put_contents('vfs://Foo/Bar.xml', '');

		$result = $this->repository->getModel('Bar');
		$this->assertAttributeContains('vfs://Foo/Bar.xml', 'sourcePaths',  $result);

		$result = $this->repository->getModel('NoSuchFile');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 */
	public function modelIsReturnedCorrectlyForGroupOfFiles() {
		mkdir('vfs://Foo/Directory');
		file_put_contents('vfs://Foo/Directory/en.xml', '');

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
		$this->assertAttributeContains('vfs://Foo/Directory/root.xml', 'sourcePaths',  $result);
		$this->assertAttributeContains('vfs://Foo/Directory/en.xml', 'sourcePaths',  $result);

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'NoSuchDirectory');
		$this->assertEquals(NULL, $result);
	}
}
