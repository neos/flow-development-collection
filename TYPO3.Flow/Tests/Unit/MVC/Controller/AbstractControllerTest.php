<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\Controller;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \F3\FLOW3\MVC\Controller\AbstractController
 */
class AbstractControllerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestWillThrowAnExceptionIfTheGivenRequestIsNotSupported() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('mapRequestArgumentsToControllerArguments'), array($this->getMock('F3\FLOW3\Object\ObjectManagerInterface')), '', FALSE);
		$controller->_set('supportedRequestTypes', array('F3\Something\Request'));
		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequestAndBuildsTheControllerContext() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext'), array(), '', FALSE);
		$controller->_set('arguments', new \F3\FLOW3\MVC\Controller\Arguments());
		$controller->_set('flashMessageContainer', new \F3\FLOW3\MVC\Controller\FlashMessageContainer());
		$controller->processRequest($mockRequest, $mockResponse);

		$this->assertInstanceOf('F3\FLOW3\MVC\Controller\ControllerContext', $controller->getControllerContext());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function forwardThrowsAStopActionException() {
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->will($this->returnValue(array()));
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(FALSE);
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified() {
		$arguments = array('foo' => 'bar');
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function forwardResetsArguments() {
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue(array()));
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array('removeAll'), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);

		$mockArguments->expects($this->once())->method('removeAll');

		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function forwardSetsSubpackageKeyIfNeeded() {
		$arguments = array('foo' => 'bar');

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with('Blub');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz\\Blub', $arguments);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function forwardResetsSubpackageKeyIfNeeded() {
		$arguments = array('foo' => 'bar');

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with(NULL);
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function forwardRemovesObjectsFromArgumentsBeforePassingThemToRequest() {
		$originalArguments = array('foo' => 'bar', 'bar' => array('someObject' => new \stdClass()));
		$convertedArguments = array('foo' => 'bar', 'bar' => array('someObject' => array('__identity' => 'x')));

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->will($this->returnValue($convertedArguments));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->once())->method('setArguments')->with($convertedArguments);

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array('removeAll'), array(), '', FALSE);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);

		$controller->_call('forward', 'someAction', 'SomeController', 'SomePackage', $originalArguments);
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

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->expects($this->once())->method('redirectToUri')->with('the uri');
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function redirectUsesRequestFormatAsDefault() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('json'));

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->will($this->returnValue($mockUriBuilder));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_call('redirect', 'show');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function redirectUsesGivenFormat() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->never())->method('getFormat');

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('pdf')->will($this->returnValue($mockUriBuilder));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_call('redirect', 'show', NULL, NULL, NULL, 0, 303, 'pdf');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function redirectToUriSetsStatus() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(303);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'));
		$controller->_set('response', $mockResponse);
		$controller->_set('request', $mockRequest);
		try {
			$controller->_call('redirectToUri', 'theUri', 1);
		} catch (\F3\FLOW3\MVC\Exception\StopActionException $exception) {}
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function redirectToUriUsesLocationHeaderOnlyIfDelayIsZero() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');
		$mockResponse->expects($this->never())->method('setHeader');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'));
		$controller->_set('response', $mockResponse);
		$controller->_set('request', $mockRequest);
		try {
			$controller->_call('redirectToUri', 'theUri', 1);
		} catch (\F3\FLOW3\MVC\Exception\StopActionException $exception) {}
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(404, 'File Really Not Found');
		$mockResponse->expects($this->once())->method('setContent')->with('<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);

		$controller->_call('throwStatus', 404, 'File Really Not Found', '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeControllerArgumentsBaseValidatorsRegistersValidatorsDeclaredInTheArgumentModels() {
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

		$mockArguments = new \F3\FLOW3\MVC\Controller\Arguments();
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->injectValidatorResolver($mockValidatorResolver);
		$controller->_call('initializeControllerArgumentsBaseValidators');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function mapRequestArgumentsToControllerArgumentsShouldPutRequestDataToArgumentObject() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$argumentFoo = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array('setValue'), array('foo', 'string'));
		$argumentFoo->setRequired(FALSE);
		$argumentBar = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array('setValue'), array('bar', 'string'));
		$argumentBar->setRequired(FALSE);

		$argumentBar->expects($this->once())->method('setValue')->with('theBarArgumentValue');

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectManager);
		$arguments->addArgument($argumentFoo);
		$arguments->addArgument($argumentBar);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('hasArgument')->with('bar')->will($this->returnValue(TRUE));
		$mockRequest->expects($this->at(2))->method('getArgument')->with('bar')->will($this->returnValue('theBarArgumentValue'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);

		$controller->_set('arguments', $arguments);
		$controller->_set('request', $mockRequest);

		$controller->_call('mapRequestArgumentsToControllerArguments');
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\MVC\Exception\RequiredArgumentMissingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function mapRequestArgumentsToControllerArgumentsShouldThrowExceptionIfRequiredArgumentWasNotSet() {
		$argumentFoo = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array('setValue'), array('foo', 'string'));
		$argumentFoo->setRequired(TRUE);

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments();
		$arguments->addArgument($argumentFoo);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\AbstractController', array('dummy'), array(), '', FALSE);

		$controller->_set('arguments', $arguments);
		$controller->_set('request', $mockRequest);

		$controller->_call('mapRequestArgumentsToControllerArguments');
	}
}
?>