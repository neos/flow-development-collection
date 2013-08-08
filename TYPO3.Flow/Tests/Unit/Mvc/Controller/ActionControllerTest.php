<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Testcase for the MVC Action Controller
 */
class ActionControllerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ActionController
	 */
	protected $actionController;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\Flow\Mvc\ViewConfigurationManager
	 */
	protected $mockViewConfigurationManager;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $mockControllerContext;

	public function setUp() {
		$this->actionController = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\ActionController', array('dummy'));

		$this->mockRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$this->mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Some.Package'));
		$this->mockRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue('Subpackage'));
		$this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('theFormat'));
		$this->mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue('TheController'));
		$this->mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('theAction'));
		$this->inject($this->actionController, 'request', $this->mockRequest);

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

		$this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
		$this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

		$this->mockViewConfigurationManager = $this->getMock('TYPO3\Flow\Mvc\ViewConfigurationManager');
		$this->inject($this->actionController, 'viewConfigurationManager', $this->mockViewConfigurationManager);
	}

	/**
	 * @test
	 */
	public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithFormatSuffixIfItExists() {
		$this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue('ResolvedObjectName'));

		$this->assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
	}

	/**
	 * @test
	 */
	public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithoutFormatSuffixIfItExists() {
		$this->mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theaction')->will($this->returnValue('ResolvedObjectName'));

		$this->assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
	}

	/**
	 * @test
	 */
	public function resolveViewObjectNameRespectsViewFormatToObjectNameMap() {
		$this->actionController->_set('viewFormatToObjectNameMap', array('html' => 'Foo', 'theFormat' => 'Some\Custom\View\Object\Name'));
		$this->mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theaction')->will($this->returnValue(FALSE));

		$this->assertSame('Some\Custom\View\Object\Name', $this->actionController->_call('resolveViewObjectName'));
	}

	/**
	 * @test
	 */
	public function resolveViewReturnsViewResolvedByResolveViewObjectName() {
		$this->mockObjectManager->expects($this->atLeastOnce())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue('ResolvedObjectName'));

		$mockView = $this->getMock('TYPO3\Flow\Mvc\View\ViewInterface');
		$this->mockObjectManager->expects($this->once())->method('get')->with('ResolvedObjectName')->will($this->returnValue($mockView));

		$this->assertSame($mockView, $this->actionController->_call('resolveView'));
	}

	/**
	 * @test
	 */
	public function resolveViewReturnsDefaultViewIfNoViewObjectNameCouldBeResolved() {
		$this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$mockView = $this->getMock('TYPO3\Flow\Mvc\View\ViewInterface');
		$this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
		$this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue($mockView));

		$this->assertSame($mockView, $this->actionController->_call('resolveView'));
	}

	/**
	 * @test
	 */
	public function processRequestInjectsControllerContextToView() {
		$this->actionController = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\ActionController', array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod', 'initializeController'));

		$this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
		$this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
		$this->inject($this->actionController, 'request', $this->mockRequest);

		$this->inject($this->actionController, 'arguments', new \TYPO3\Flow\Mvc\Controller\Arguments(array()));

		$mockMvcPropertyMappingConfigurationService = $this->getMock('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService');
		$this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
		$this->mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

		$mockResponse = $this->getMock('TYPO3\Flow\Http\Response');

		$mockView = $this->getMock('TYPO3\Flow\Mvc\View\ViewInterface');
		$mockView->expects($this->once())->method('setControllerContext')->with($this->mockControllerContext);
		$this->actionController->expects($this->once())->method('resolveView')->will($this->returnValue($mockView));

		$this->actionController->processRequest($this->mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function processRequestInjectsSettingsToView() {
		$this->actionController = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\ActionController', array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod'));

		$this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
		$this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

		$mockSettings = array('foo', 'bar');
		$this->inject($this->actionController, 'settings', $mockSettings);

		$mockMvcPropertyMappingConfigurationService = $this->getMock('TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService');
		$this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
		$this->mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

		$mockResponse = $this->getMock('TYPO3\Flow\Http\Response');

		$mockView = $this->getMock('TYPO3\Flow\Mvc\View\ViewInterface');
		$mockView->expects($this->once())->method('assign')->with('settings', $mockSettings);
		$this->actionController->expects($this->once())->method('resolveView')->will($this->returnValue($mockView));

		$this->actionController->processRequest($this->mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\ViewNotFoundException
	 */
	public function resolveViewThrowsExceptionIfResolvedViewDoesNotImplementViewInterface() {
		$this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
		$invalidView = new \stdClass();
		$this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue($invalidView));

		$this->actionController->_call('resolveView');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\ViewNotFoundException
	 */
	public function resolveViewThrowsExceptionIfViewCouldNotBeResolved() {
		$this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
		$this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue(NULL));

		$this->actionController->_call('resolveView');
	}
}
?>