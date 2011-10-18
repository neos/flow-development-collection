<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web;

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
 * Testcase for the MVC Web SubRequestBuilder class
 *
 */
class SubRequestBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\SubRequest
	 */
	protected $mockSubRequest;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\SubRequestBuilder
	 */
	protected $subRequestBuilder;

	/**
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
	 */
	protected $mockUri;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->mockUri = $this->getMock('TYPO3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockUri->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$this->mockSubRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$this->mockSubRequest->expects($this->any())->method('getRequestUri')->will($this->returnValue($this->mockUri));
		$this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockRequest));
		$this->subRequestBuilder = new \TYPO3\FLOW3\MVC\Web\SubRequestBuilder();
		$this->subRequestBuilder->injectObjectManager($this->mockObjectManager);
		$this->subRequestBuilder->injectEnvironment($this->mockEnvironment);
	}

	/**
	 * @test
	 */
	public function buildSetsParentRequest() {
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$result = $this->subRequestBuilder->build($this->mockRequest);
		$this->assertSame($this->mockRequest, $result->getParentRequest());
	}

	/**
	 * @test
	 */
	public function buildSetsArgumentNamespaceToAnEmptyStringByDefault() {
		$result = $this->subRequestBuilder->build($this->mockRequest);
		$this->assertEquals('', $result->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function buildSetsSpecifiedArgumentNamespace() {
		$result = $this->subRequestBuilder->build($this->mockRequest, 'SomeArgumentNamespace');
		$this->assertEquals('SomeArgumentNamespace', $result->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function buildDoesNotSetAnyArgumentsThatAreNotPrefix() {
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(FALSE));
		$this->mockRequest->expects($this->never())->method('getArgument');

		$result = $this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
		$this->assertEquals(array(), $result->getArguments());
	}

	/**
	 * @test
	 */
	public function buildIgnoresNamespacedArgumentsOfTypeString() {
		$arguments = array(
			'nonPrefixedArgument' => 'should be ignored',
			'argumentNamespace' => 'should be an array'
		);
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(TRUE));
		$this->mockRequest->expects($this->atLeastOnce())->method('getArgument')->with('argumentNamespace')->will($this->returnValue($arguments['argumentNamespace']));

		$result = $this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
		$this->assertEquals(array(), $result->getArguments());
	}

	/**
	 * @test
	 */
	public function buildSetsNamespacedArgumentsFromParentRequest() {
		$arguments = array(
			'nonPrefixedArgument' => 'should be ignored',
			'argumentNamespace' => array(
				'prefixedArgument1' => 'argumentValue1',
				'prefixedArgument2' => 'argumentValue2',
				'prefixedArgument3' => array(
					'foo' => 'bar'
				)
			)
		);
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(TRUE));
		$this->mockRequest->expects($this->atLeastOnce())->method('getArgument')->with('argumentNamespace')->will($this->returnValue($arguments['argumentNamespace']));

		$result = $this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
		$this->assertEquals($arguments['argumentNamespace'], $result->getArguments());
	}

	/**
	 * @test
	 */
	public function buildCallsSetControllerKeysAndFormat() {
		$mockBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\SubRequestBuilder', array('setControllerKeysAndFormat', 'setArgumentsFromRawRequestData'));
		$mockBuilder->expects($this->once())->method('setControllerKeysAndFormat');
		$mockBuilder->build($this->mockRequest, 'argumentNamespace');

	}

	/**
	 * @test
	 */
	public function setControllerKeysAndFormatSetsControllerKeysAndFormat() {
		$mockBuilder = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\SubRequestBuilder', array('dummy'));
		$prefixedArguments = array(
			'prefixedArgument1' => 'argumentValue1',
			'@package' => 'SomePackageKey',
			'@subpackage' => 'SomeSubpackageKey',
			'@controller' => 'SomeControllerName',
			'@action' => 'SomeActionName',
			'@format' => 'SomeFormat',
		);
		$this->mockSubRequest->expects($this->atLeastOnce())->method('getArguments')->will($this->returnValue($prefixedArguments));
		$this->mockSubRequest->expects($this->once())->method('setControllerPackageKey')->with('SomePackageKey');
		$this->mockSubRequest->expects($this->once())->method('setControllerSubpackageKey')->with('SomeSubpackageKey');
		$this->mockSubRequest->expects($this->once())->method('setControllerName')->with('SomeControllerName');
		$this->mockSubRequest->expects($this->once())->method('setControllerActionName')->with('SomeActionName');
		$this->mockSubRequest->expects($this->once())->method('setFormat')->with('someformat');

		$mockBuilder->_call('setControllerKeysAndFormat', $this->mockSubRequest);
	}

}

?>