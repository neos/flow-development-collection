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
 * Testcase for the MVC StandardView
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StandardViewTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Resource\Manager
	 */
	protected $recourceManager;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \F3\FLOW3\MVC\View\NotFoundView
	 */
	protected $view;

	public function setUp() {
		$this->objectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$this->packageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface', array(), array(), '', FALSE);
		$this->resourceManager = $this->getMock('F3\FLOW3\Resource\Manager', array(), array(), '', FALSE);
		$this->objectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);

		$this->view = new \F3\FLOW3\MVC\View\StandardView($this->objectFactory, $this->packageManager, $this->resourceManager, $this->objectManager);

		$this->controllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext', array('getRequest'));
		$this->view->setControllerContext($this->controllerContext);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionIfNoRequestIsAvailable() {
		$this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue(NULL));

		$this->view->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsContentOfTemplateAndReplacesBaseUriMarkerIfRequestIsWebRequest() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\Web\Request', array('getBaseURI'));
		$mockRequest->expects($this->any())->method('getBaseURI')->will($this->returnValue('someBaseUri'));
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateContent = file_get_contents(FLOW3_PATH_FLOW3 . 'Resources/Private/MVC/StandardView_Template.html');
		$templateContent = str_replace('###BASEURI###', 'someBaseUri', $templateContent);

		$this->assertSame($templateContent, $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsContentOfTemplateAndReplacesBaseUriMarkerIfRequestIsNoWebRequest() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->never())->method('getBaseURI');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateContent = file_get_contents(FLOW3_PATH_FLOW3 . 'Resources/Private/MVC/StandardView_Template.html');

		$this->assertSame($templateContent, $this->view->render());
		$this->assertContains('###BASEURI###', $templateContent);
	}
}
?>