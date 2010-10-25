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
class CldrRepositoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\I18n\Cldr\CldrRepository
	 */
	protected $repository;

	/**
	 * @return void
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));

		$this->repository = $this->getAccessibleMock('F3\FLOW3\I18n\Cldr\CldrRepository', array('dummy'));
		$this->repository->_set('cldrBasePath', 'vfs://Foo/');
	}

	/**
	 * @test
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function modelIsReturnedCorrectly() {
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
	 * @author Karol Gusak <firstname@lastname.eu>
	 */
	public function modelCollectionIsReturnedCorrectly() {
		mkdir('vfs://Foo/Folder');
		file_put_contents('vfs://Foo/Folder/en.xml', '');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('create')->with('F3\FLOW3\I18n\Cldr\CldrModel', 'vfs://Foo/Folder/en.xml')->will($this->returnValue('en.xml Model'));
		$mockObjectManager->expects($this->at(1))->method('create')->with('F3\FLOW3\I18n\Cldr\CldrModel', 'vfs://Foo/Folder/root.xml')->will($this->returnValue('root.xml Model'));
		$mockObjectManager->expects($this->at(2))->method('create')->with('F3\FLOW3\I18n\Cldr\CldrModelCollection', array('en.xml Model', 'root.xml Model'))->will($this->returnValue('ModelCollectionWouldBeHere'));

		$mockLocalizationService = $this->getMock('F3\FLOW3\I18n\Service');
		$mockLocalizationService->expects($this->once())->method('getParentLocaleOf')->will($this->returnValue(NULL));

		$this->repository->injectObjectManager($mockObjectManager);
		$this->repository->injectLocalizationService($mockLocalizationService);

		$result = $this->repository->getModelCollection('Folder', new \F3\FLOW3\I18n\Locale('en'));
		$this->assertEquals('ModelCollectionWouldBeHere', $result);

			// Second access should not invoke objectManager requests
		$result = $this->repository->getModelCollection('Folder', new \F3\FLOW3\I18n\Locale('en'));
		$this->assertEquals('ModelCollectionWouldBeHere', $result);

		$result = $this->repository->getModelCollection('NoSuchDirectory', new \F3\FLOW3\I18n\Locale('en'));
		$this->assertEquals(FALSE, $result);
	}
}

?>