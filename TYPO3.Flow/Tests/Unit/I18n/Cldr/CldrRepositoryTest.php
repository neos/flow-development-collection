<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\I18n\Cldr;

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
 * Testcase for the CldrRepository
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CldrRepositoryTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Cldr\CldrRepository
	 */
	protected $repository;

	/**
	 * @var \F3\FLOW3\I18n\Locale
	 */
	protected $dummyLocale;

	/**
	 * @return void
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		$this->repository = $this->getAccessibleMock('F3\FLOW3\I18n\Cldr\CldrRepository', array('dummy'));
		$this->repository->_set('cldrBasePath', 'vfs://Foo/');

		$this->dummyLocale = new \F3\FLOW3\I18n\Locale('en');
	}

	/**
	 * @test
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function modelIsReturnedCorrectlyForSingleFile() {
		file_put_contents('vfs://Foo/Bar.xml', '');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\I18n\Cldr\CldrModel')->will($this->returnValue('ModelWouldBeHere'));
		$this->repository->injectObjectManager($mockObjectManager);

		$result = $this->repository->getModel('Bar');
		$this->assertEquals('ModelWouldBeHere', $result);

			// Second access should not invoke objectManager request
		$result = $this->repository->getModel('Bar');
		$this->assertEquals('ModelWouldBeHere', $result);

		$result = $this->repository->getModel('NoSuchFile');
		$this->assertEquals(FALSE, $result);
	}

	/**
	 * @test
	 * @author Karol Gusak <karol@gusak.eu>
	 */
	public function modelIsReturnedCorrectlyForGroupOfFiles() {
		mkdir('vfs://Foo/Directory');
		file_put_contents('vfs://Foo/Directory/en.xml', '');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\I18n\Cldr\CldrModel', array('vfs://Foo/Directory/root.xml', 'vfs://Foo/Directory/en.xml'))->will($this->returnValue('ModelWouldBeHere'));

		$mockLocalizationService = $this->getMock('F3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getParentLocaleOf')->will($this->returnValue(NULL));

		$this->repository->injectObjectManager($mockObjectManager);
		$this->repository->injectLocalizationService($mockLocalizationService);

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
		$this->assertEquals('ModelWouldBeHere', $result);

			// Second access should not invoke objectManager requests
		$result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
		$this->assertEquals('ModelWouldBeHere', $result);

		$result = $this->repository->getModelForLocale($this->dummyLocale, 'NoSuchDirectory');
		$this->assertEquals(FALSE, $result);
	}
}

?>