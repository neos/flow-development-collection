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
 * Testcase for the MVC Abstract Controller
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException F3\FLOW3\MVC\Exception\UnsupportedRequestType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestWillThrowAnExceptionIfTheGivenRequestIsNotSupported() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('mapRequestArgumentsToControllerArguments'), array($this->getMock('F3\FLOW3\Object\FactoryInterface')), '', FALSE);
		$controller->_set('supportedRequestTypes', array('F3\Something\Request'));
		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequest() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\Routing\UriBuilder')->will($this->returnValue($mockUriBuilder));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments'), array(), '', FALSE);
		$controller->_set('objectFactory', $mockObjectFactory);
		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildControllerContextCreatesControllerContextWithRequiredPropeties() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface');
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockArgumentsMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults');
		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockFlashMessageContainer = $this->getMock('F3\FLOW3\MVC\Controller\FlashMessageContainer');

		$mockControllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext');
		$mockControllerContext->expects($this->once())->method('setRequest')->with($mockRequest);
		$mockControllerContext->expects($this->once())->method('setResponse')->with($mockResponse);
		$mockControllerContext->expects($this->once())->method('setArguments')->with($mockArguments);
		$mockControllerContext->expects($this->once())->method('setArgumentsMappingResults')->with($mockArgumentsMappingResults);
		$mockControllerContext->expects($this->once())->method('setUriBuilder')->with($mockUriBuilder);
		$mockControllerContext->expects($this->once())->method('setFlashMessageContainer')->with($mockFlashMessageContainer);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Controller\ControllerContext')->will($this->returnValue($mockControllerContext));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('objectFactory', $mockObjectFactory);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('argumentsMappingResults', $mockArgumentsMappingResults);
		$controller->_set('flashMessageContainer', $mockFlashMessageContainer);
		$controller->_call('buildControllerContext');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function forwardThrowsAStopActionException() {
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(FALSE);
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified() {
		$arguments = array('foo' => 'bar');

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function forwardResetsArguments() {
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array('removeAll'), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);

		$mockArguments->expects($this->once())->method('removeAll');

		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function redirectRedirectsToTheSpecifiedAction() {
		$arguments = array('foo' => 'bar');
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->will($this->returnValue('the uri'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('redirectToURI'), array(), '', FALSE);
		$controller->expects($this->once())->method('redirectToURI')->with('the uri');
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(404, 'File Really Not Found');
		$mockResponse->expects($this->once())->method('setContent')->with('<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);

		$controller->_call('throwStatus', 404, 'File Really Not Found', '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeControllerArgumentsBaseValidatorsRegistersValidatorsDeclaredInTheArgumentModels() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$mockValidators = array(
			'foo' => $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface'),
		);

		$mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->at(0))->method('getBaseValidatorConjunction')->with('FooType')->will($this->returnValue($mockValidators['foo']));
		$mockValidatorResolver->expects($this->at(1))->method('getBaseValidatorConjunction')->with('BarType')->will($this->returnValue(NULL));

		$mockArgumentFoo = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array('foo', 'FooType'));
		$mockArgumentFoo->expects($this->once())->method('getDataType')->will($this->returnValue('FooType'));
		$mockArgumentFoo->expects($this->once())->method('setValidator')->with($mockValidators['foo']);

		$mockArgumentBar = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array('bar', 'barType'));
		$mockArgumentBar->expects($this->once())->method('getDataType')->will($this->returnValue('BarType'));
		$mockArgumentBar->expects($this->never())->method('setValidator');

		$mockArguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->injectValidatorResolver($mockValidatorResolver);
		$controller->_call('initializeControllerArgumentsBaseValidators');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapRequestArgumentsToControllerArgumentsPreparesInformationAndValidatorsAndMapsAndValidates() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$mockValidator = $this->getMock('F3\FLOW3\MVC\Controller\ArgumentsValidator', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\MVC\Controller\ArgumentsValidator')->will($this->returnValue($mockValidator));

		$mockArgumentFoo = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array('foo', 'fooType'));
		$mockArgumentFoo->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$mockArgumentBar = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array('bar', 'barType'));
		$mockArgumentBar->expects($this->any())->method('getName')->will($this->returnValue('bar'));

		$mockArguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('requestFoo', 'requestBar')));

		$mockMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults');

		$mockPropertyMapper = $this->getMock('F3\FLOW3\Property\Mapper', array(), array(), '', FALSE);
		$mockPropertyMapper->expects($this->once())->method('mapAndValidate')->
			with(array('foo', 'bar'), array('requestFoo', 'requestBar'), $mockArguments, array(), $mockValidator)->
			will($this->returnValue(TRUE));
		$mockPropertyMapper->expects($this->once())->method('getMappingResults')->will($this->returnValue($mockMappingResults));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\AbstractController'), array('dummy'), array(), '', FALSE);

		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('propertyMapper', $mockPropertyMapper);
		$controller->_set('objectManager', $mockObjectManager);

		$controller->_call('mapRequestArgumentsToControllerArguments');

		$this->assertSame($mockMappingResults, $controller->_get('argumentsMappingResults'));
	}
}
?>