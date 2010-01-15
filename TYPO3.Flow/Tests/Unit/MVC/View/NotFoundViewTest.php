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

require_once('vfs/vfsStream.php');

/**
 * Testcase for the MVC NotFoundView
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NotFoundViewTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectFactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Resource\ResourceManager
	 */
	protected $recourceManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\MVC\Controller\Context
	 */
	protected $controllerContext;

	/**
	 * @var \F3\FLOW3\MVC\View\NotFoundView
	 */
	protected $view;

	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$this->objectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$this->packageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface', array(), array(), '', FALSE);
		$this->resourceManager = $this->getMock('F3\FLOW3\Resource\ResourceManager', array(), array(), '', FALSE);
		$this->objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$this->view = $this->getMock('F3\FLOW3\MVC\View\NotFoundView', array('getTemplatePathAndFilename'), array($this->objectFactory, $this->packageManager, $this->resourceManager, $this->objectManager));

		$this->controllerContext = $this->getMock('F3\FLOW3\MVC\Controller\Context', array('getRequest'), array(), '', FALSE);
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
	public function renderReturnsContentOfTemplateReturnedByGetTemplatePathAndFilename() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'template content');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('template content', $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReplacesErrorMessageMarker() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'error message: ###ERROR_MESSAGE###');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->view->assign('errorMessage', 'some error message');

		$this->assertSame('error message: some error message', $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReplacesErrorMessageMarkerWithEmptyStringIfNoErrorMessageIsSet() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'error message: ###ERROR_MESSAGE###');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('error message: ', $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReplacesBaseUriMarkerIfRequestIsWebRequest() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\Web\Request', array('getBaseUri'));
		$mockRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue('someBaseUri'));
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'base URI: ###BASEURI###');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('base URI: someBaseUri', $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderDoesNotReplaceBaseUriMarkerIfRequestIsNoWebRequest() {
		$mockRequest = $this->getMock('\F3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->never())->method('getBaseUri');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'base URI: ###BASEURI###');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('base URI: ###BASEURI###', $this->view->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingNonExistingMethodsWontThrowAnException() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManagerInterface', array(), array(), '', FALSE);
		$mockResourceManager = $this->getMock('F3\FLOW3\Resource\ResourceManager', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$view = new \F3\FLOW3\MVC\View\NotFoundView($mockObjectFactory, $mockPackageManager, $mockResourceManager, $mockObjectManager);
		$view->nonExistingMethod();
	}
}
?>