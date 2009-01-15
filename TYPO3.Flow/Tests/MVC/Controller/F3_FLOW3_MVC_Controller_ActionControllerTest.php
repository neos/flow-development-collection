<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

require_once(__DIR__ . '/../Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockActionController.php');

/**
 * Testcase for the MVC Action Controller
 *
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ActionControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringsReturnedByActionMethodAreAppendedToResponseObject() {
		$mockController = new \F3\FLOW3\MVC\Fixture\Controller\MockActionController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$mockController->injectObjectManager($this->objectManager);
		$mockController->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$request->setControllerActionName('returnSomeString');
		$mockController->processRequest($request, $response);
		$this->assertEquals('Mock Action Controller Return String', $response->getContent(), 'The response object did not contain the string returned by the action controller');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function ifNoViewCouldBeResolvedAnEmptyViewIsProvided() {
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ActionController', array('exoticAction'), array($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface')), 'F3\FLOW3\MVC\Controller\ActionController' . uniqid());
		$mockController->injectObjectManager($this->objectManager);
		$mockController->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('DefaultController');
		$request->setControllerActionName('exotic');

		$mockController->processRequest($request, $response);
		$viewReflection = new \F3\FLOW3\Reflection\PropertyReflection(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('F3\FLOW3\MVC\View\AbstractView', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertEquals('F3\FLOW3\MVC\View\EmptyView', get_class($view), 'The action controller did not provide an empty view.');
	}

	/**
	 * Views following the scheme \F3\PackageName\View\ActionName will be set as $this->view
	 * automatically.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function aViewMatchingTheActionNameIsProvidedAutomatically() {
		$mockController = $this->getMock('F3\FLOW3\MVC\Controller\ActionController', array('thingAction'), array($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface')), 'F3\FLOW3\MVC\Controller\ActionController' . uniqid());
		$mockController->injectObjectManager($this->objectManager);
		$mockController->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Some');
		$request->setControllerActionName('thing');
		$request->setViewObjectNamePattern('F3\@package\View\@controller\@action');

		$mockController->processRequest($request, $response);
		$viewReflection = new \F3\FLOW3\Reflection\PropertyReflection(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('F3\FLOW3\MVC\View\AbstractView', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertEquals('F3\TestPackage\View\Some\Thing', get_class($view), 'The action controller did not select the "Some" "Thing" view.');
	}
}
?>