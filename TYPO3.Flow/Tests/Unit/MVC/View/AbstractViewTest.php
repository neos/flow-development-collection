<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * Testcase for the MVC AbstractView
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractViewTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsDependencies() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface', array(), array(), '', FALSE);
		$mockResourceManager = $this->getMock('F3\FLOW3\Resource\ResourceManager', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$view = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\View\AbstractView'), array('render'), array($mockObjectFactory, $mockPackageManager, $mockResourceManager, $mockObjectManager));
		$this->assertSame($mockObjectFactory, $view->_get('objectFactory'));
		$this->assertSame($mockPackageManager, $view->_get('packageManager'));
		$this->assertSame($mockResourceManager, $view->_get('resourceManager'));
		$this->assertSame($mockObjectManager, $view->_get('objectManager'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function initializeViewIsCalledUponObjectInitialization() {
		$view = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\View\AbstractView'), array('initializeView', 'render'), array(), '', FALSE);
		$view->expects($this->once())->method('initializeView');
		$view->initializeObject();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignStoresValueInViewDataCollection() {
		$view = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\View\AbstractView'), array('render'), array(), '', FALSE);
		$view->assign('someKey', 'someValue');
		$view->assign('someOtherKey', 'someOtherValue');

		$this->assertEquals(array('someKey' => 'someValue', 'someOtherKey' => 'someOtherValue'), $view->_get('viewData'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignReturnsTheViewToEnableChaining() {
		$abstractView = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\View\AbstractView'), array('render'), array(), '', FALSE);
		$actualResult = $abstractView->assign('someKey', 'someValue');

		$this->assertSame($abstractView, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function assignMultipleAllowsToSetMultipleValues() {
		$abstractView = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\View\AbstractView'), array('render'), array(), '', FALSE);
		$abstractView->assignMultiple(array('someKey' => 'someValue', 'someOtherKey' => 'someOtherValue'));

		$this->assertEquals(array('someKey' => 'someValue', 'someOtherKey' => 'someOtherValue'), $abstractView->_get('viewData'));
	}
}
?>