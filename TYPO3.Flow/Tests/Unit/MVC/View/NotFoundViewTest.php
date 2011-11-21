<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\View;

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
 *
 */
class NotFoundViewTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var \TYPO3\FLOW3\MVC\View\NotFoundView
	 */
	protected $view;

	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$this->view = $this->getMock('TYPO3\FLOW3\MVC\View\NotFoundView', array('getTemplatePathAndFilename'));

		$this->controllerContext = $this->getMock('TYPO3\FLOW3\MVC\Controller\ControllerContext', array('getRequest'), array(), '', FALSE);
		$this->view->setControllerContext($this->controllerContext);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\MVC\Exception
	 */
	public function renderThrowsExceptionIfNoRequestIsAvailable() {
		$this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue(NULL));

		$this->view->render();
	}

	/**
	 * @test
	 */
	public function renderReturnsContentOfTemplateReturnedByGetTemplatePathAndFilename() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\MVC\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'template content');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('template content', $this->view->render());
	}

	/**
	 * @test
	 */
	public function renderReplacesErrorMessageMarker() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\MVC\RequestInterface');
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
		$mockRequest = $this->getMock('\TYPO3\FLOW3\MVC\RequestInterface');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'error message: {ERROR_MESSAGE}');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('error message: ', $this->view->render());
	}

	/**
	 * @test
	 */
	public function renderReplacesBaseUriMarkerIfRequestIsWebRequest() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\MVC\Web\Request', array('getBaseUri'));
		$mockRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue('someBaseUri'));
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'base URI: {BASEURI}');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('base URI: someBaseUri', $this->view->render());
	}

	/**
	 * @test
	 */
	public function renderDoesNotReplaceBaseUriMarkerIfRequestIsNoWebRequest() {
		$mockRequest = $this->getMock('\TYPO3\FLOW3\MVC\RequestInterface');
		$mockRequest->expects($this->never())->method('getBaseUri');
		$this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$templateUrl = \vfsStream::url('testDirectory') . '/template.html';
		file_put_contents($templateUrl, 'base URI: {BASEURI}');
		$this->view->expects($this->once())->method('getTemplatePathAndFilename')->will($this->returnValue($templateUrl));

		$this->assertSame('base URI: {BASEURI}', $this->view->render());
	}

	/**
	 * @test
	 */
	public function callingNonExistingMethodsWontThrowAnException() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockPackageManager = $this->getMock('TYPO3\FLOW3\Package\PackageManagerInterface', array(), array(), '', FALSE);
		$mockResourceManager = $this->getMock('TYPO3\FLOW3\Resource\ResourceManager', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$view = new \TYPO3\FLOW3\MVC\View\NotFoundView($mockObjectManager, $mockPackageManager, $mockResourceManager, $mockObjectManager);
		$view->nonExistingMethod();
	}
}
?>