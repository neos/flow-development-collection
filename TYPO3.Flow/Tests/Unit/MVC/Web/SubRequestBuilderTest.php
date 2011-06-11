<?php
namespace F3\FLOW3\Tests\Unit\MVC\Web;

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
 * Testcase for the MVC Web SubRequestBuilder class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SubRequestBuilderTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $mockRequest;

	/**
	 * @var \F3\FLOW3\MVC\Web\SubRequest
	 */
	protected $mockSubRequest;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * @var \F3\FLOW3\MVC\Web\SubRequestBuilder
	 */
	protected $subRequestBuilder;

	/**
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $mockUri;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->mockUri = $this->getMock('F3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockUri->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$this->mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->mockSubRequest = $this->getMock('F3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$this->mockSubRequest->expects($this->any())->method('getRequestUri')->will($this->returnValue($this->mockUri));
		$this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockRequest));
		$this->subRequestBuilder = new \F3\FLOW3\MVC\Web\SubRequestBuilder();
		$this->subRequestBuilder->injectObjectManager($this->mockObjectManager);
		$this->subRequestBuilder->injectEnvironment($this->mockEnvironment);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsParentRequest() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->subRequestBuilder->build($this->mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsArgumentNamespaceToAnEmptyStringByDefault() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockSubRequest->expects($this->once())->method('setArgumentNamespace')->with('');
		$this->subRequestBuilder->build($this->mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsSpecifiedArgumentNamespace() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockSubRequest->expects($this->once())->method('setArgumentNamespace')->with('SomeArgumentNamespace');
		$this->subRequestBuilder->build($this->mockRequest, 'SomeArgumentNamespace');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildDoesNotSetAnyArgumentsThatAreNotPrefix() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockSubRequest->expects($this->once())->method('getArgumentNamespace')->will($this->returnValue('argumentNamespace'));
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(FALSE));
		$this->mockRequest->expects($this->never())->method('getArgument');
		$this->mockSubRequest->expects($this->never())->method('setArguments');

		$this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildIgnoresNamespacedArgumentsOfTypeString() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$arguments = array(
			'nonPrefixedArgument' => 'should be ignored',
			'argumentNamespace' => 'should be an array'
		);
		$this->mockSubRequest->expects($this->once())->method('getArgumentNamespace')->will($this->returnValue('argumentNamespace'));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(TRUE));
		$this->mockRequest->expects($this->atLeastOnce())->method('getArgument')->with('argumentNamespace')->will($this->returnValue($arguments['argumentNamespace']));
		$this->mockSubRequest->expects($this->never())->method('setArguments');

		$this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsNamespacedArgumentsFromParentRequest() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
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
		$this->mockSubRequest->expects($this->once())->method('getArgumentNamespace')->will($this->returnValue('argumentNamespace'));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockRequest->expects($this->once())->method('hasArgument')->with('argumentNamespace')->will($this->returnValue(TRUE));
		$this->mockRequest->expects($this->atLeastOnce())->method('getArgument')->with('argumentNamespace')->will($this->returnValue($arguments['argumentNamespace']));
		$this->mockSubRequest->expects($this->once())->method('setArguments')->with($arguments['argumentNamespace']);

		$this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsControllerKeysAndFormat() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\SubRequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
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

		$this->subRequestBuilder->build($this->mockRequest, 'argumentNamespace');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function buildCreatesSpecifiedObject() {
		$this->mockObjectManager->expects($this->once())->method('create')->with('F3\My\Custom\Subrequest', $this->mockRequest)->will($this->returnValue($this->mockSubRequest));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->subRequestBuilder->build($this->mockRequest, 'SomeArgumentNamespace', 'F3\My\Custom\Subrequest');
	}
}
?>