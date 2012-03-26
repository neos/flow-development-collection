<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\View;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC NotFoundView
 */
class NotFoundViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \TYPO3\FLOW3\Mvc\View\NotFoundView
	 */
	protected $view;

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $request;

	/**
	 * @var \TYPO3\FLOW3\Http\Response
	 */
	protected $response;

	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$this->view = $this->getMock('TYPO3\FLOW3\Mvc\View\NotFoundView', array('getTemplatePathAndFilename'));

		$httpRequest = \TYPO3\FLOW3\Http\Request::create(new \TYPO3\FLOW3\Http\Uri('http://typo3.org'));
		$this->request = $httpRequest->createActionRequest();
		$this->response = new \TYPO3\FLOW3\Http\Response();

		$this->controllerContext = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerContext', array('getRequest', 'getResponse'), array(), '', FALSE);
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
		$this->controllerContext->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

		$this->view->setControllerContext($this->controllerContext);
	}

	/**
	 * @test
	 */
	public function renderReturnsContentOfTemplateReturnedByGetTemplatePathAndFilename() {
		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'template content');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('template content', $this->view->render());
	}

	/**
	 * @test
	 */
	public function renderReplacesErrorMessageMarker() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\Mvc\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'error message: {ERROR_MESSAGE}');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->view->assign('errorMessage', 'some error message');

		$this->assertSame('error message: some error message', $this->view->render());
	}

	/**
	 * @test
	 */
	public function renderReplacesErrorMessageMarkerWithEmptyStringIfNoErrorMessageIsSet() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\Mvc\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'error message: {ERROR_MESSAGE}');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('error message: ', $this->view->render());
	}

	/**
	 * @test
	 */
	public function callingNonExistingMethodsWontThrowAnException() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface');
		$mockResourceManager = $this->getMock('TYPO3\FLOW3\Resource\ResourceManager');

		$view = new \TYPO3\FLOW3\Mvc\View\NotFoundView($mockObjectManager, $mockPackageManager, $mockResourceManager, $mockObjectManager);
		$view->nonExistingMethod();
			// dummy assertion to avoid "This test did not perform any assertions" warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function renderSets404Status() {
		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'template content');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->view->render();
		$this->assertEquals('404 Not Found', $this->response->getStatus());
	}
}
?>
