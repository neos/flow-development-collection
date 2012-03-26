<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Web;

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
 * Testcase for the MVC Web Request Builder
 *
 */
class RequestBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * The mocked request
	 *
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $requestUri;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $mockEnvironment;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $mockConfigurationManager;

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequestBuilder
	 */
	protected $builder;

	/**
	 * Sets up a request builder for testing
	 *
	 * @return void
	 */
	protected function setUpRequestBuilder() {
		if ($this->requestUri === NULL) {
			$this->requestUri = new \TYPO3\FLOW3\Http\Uri('http://localhost/foo?someArgument=GETArgument');
		}

		$baseUri = new \TYPO3\FLOW3\Http\Uri('http://localhost');

		$this->mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('getRequestUri', 'getBaseUri', 'getRequestMethod'), array(), '', FALSE);
		$this->mockEnvironment->expects($this->any())->method('getRequestMethod')->will($this->returnValue('GET'));
		$this->mockEnvironment->expects($this->any())->method('getRequestUri')->will($this->returnValue($this->requestUri));
		$this->mockEnvironment->expects($this->any())->method('getBaseUri')->will($this->returnValue($baseUri));

		$this->mockConfigurationManager = $this->getMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration'), array(), '', FALSE);
		$this->mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue(array()));

		$this->mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface', array('route', 'setRoutesConfiguration', 'resolve', 'getControllerObjectName'));

		$this->builder = new \TYPO3\FLOW3\Mvc\ActionRequestBuilder();
		$this->builder->injectEnvironment($this->mockEnvironment);
		$this->builder->injectConfigurationManager($this->mockConfigurationManager);
		$this->builder->injectRouter($this->mockRouter);
	}

	/**
	 * @test
	 */
	public function buildReturnsAWebRequestObject() {
		$this->setUpRequestBuilder();

		$request = $this->builder->build();
		$this->assertInstanceOf('TYPO3\FLOW3\Mvc\ActionRequest', $request);
	}

	/**
	 * @test
	 */
	public function buildInvokesTheRouteMethodOfTheRouter() {
		$this->setUpRequestBuilder();
		$this->mockRouter->expects($this->once())->method('route');
		$this->builder->build();
	}

	/**
	 * @test
	 */
	public function buildDetectsTheRequestMethodAndSetsItInTheRequestObject() {
		$this->setUpRequestBuilder();
		$this->mockEnvironment->expects($this->any())->method('getRequestMethod')->will($this->returnValue('GET'));

		$request = $this->builder->build();
		$this->assertEquals('GET', $request->getMethod());
	}

	/**
	 * @test
	 */
	public function buildSetsGETArgumentsFromRequest() {
		$this->setUpRequestBuilder();

		$request = $this->builder->build();
		$arguments = $request->getArguments();
		$this->assertEquals(array('someArgument' => 'GETArgument'), $arguments);
	}

	/**
	 * @test
	 */
	public function buildSetsPOSTArgumentsFromRequest() {
		$this->setUpRequestBuilder();

		$this->mockEnvironment->expects($this->any())->method('getRequestMethod')->will($this->returnValue('POST'));
		$this->mockEnvironment->expects($this->any())->method('getRawPostArguments')->will($this->returnValue(array('someArgument' => 'POSTArgument')));
		$this->mockEnvironment->expects($this->any())->method('getUploadedFiles')->will($this->returnValue(array()));

		$this->builder->build();
	}

	/**
	 * @test
	 */
	public function setArgumentsFromRawRequestDataRecursivelyMergesGETAndPOSTArgumentsFromRequest() {
		$getArguments = array(
			'getArgument1' => 'getArgument1Value',
			'getArgument2' => array(
				'getArgument2a' => 'getArgument2aValue',
				'getArgument2b' => 'getArgument2bValue'
			),
			'argument3' => 'argument3Value',
			'argument4' => array(
				'argument4a' => 'argument4aValue',
				'argument4b' => array(
					'argument4ba' => 'argument4baValue',
				)
			),
			'argument5' => 'argument5Value',
		);

		$postArguments = array(
			'postArgument1' => 'postArgument1Value',
			'postArgument2' => array(
				'postArgument2a' => 'postArgument2aValue',
				'postArgument2b' => 'postArgument2bValue'
			),
			'argument3' => 'overriddenArgument3Value',
			'argument4' => array(
				'argument4a' => 'overriddenArgument4aValue',
				'argument4b' => array(
					'argument4bb' => 'argument4bbValue',
				),
				'argument4c' => 'argument4cValue',
			),
			'argument6' => 'argument6Value',
		);
		$expectedArguments = array(
			'getArgument1' => 'getArgument1Value',
			'getArgument2' => array(
				'getArgument2a' => 'getArgument2aValue',
				'getArgument2b' => 'getArgument2bValue'
			),
			'argument3' => 'overriddenArgument3Value',
			'argument4' => array(
				'argument4a' => 'overriddenArgument4aValue',
				'argument4b' => array(
					'argument4ba' => 'argument4baValue',
					'argument4bb' => 'argument4bbValue',
				),
				'argument4c' => 'argument4cValue',
			),
			'argument5' => 'argument5Value',
			'postArgument1' => 'postArgument1Value',
			'postArgument2' => array(
				'postArgument2a' => 'postArgument2aValue',
				'postArgument2b' => 'postArgument2bValue'
			),
			'argument6' => 'argument6Value',
		);

		$mockRequestUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$mockRequestUri->expects($this->once())->method('getArguments')->will($this->returnValue($getArguments));

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRawPostArguments')->will($this->returnValue($postArguments));
		$mockEnvironment->expects($this->any())->method('getUploadedFiles')->will($this->returnValue(array()));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getMethod', 'getRequestUri'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getRequestUri')->will($this->returnValue($mockRequestUri));
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('POST'));

		$builder = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\ActionRequestBuilder', array('dummy'), array(), '', FALSE);
		$builder->injectEnvironment($mockEnvironment);
		$builder->_call('setArgumentsFromRawRequestData', $mockRequest);

		$this->assertSame($expectedArguments, $mockRequest->getArguments());
	}

	/**
	 * @test
	 */
	public function setArgumentsFromRawRequestDataMergesUploadedFilesInformationIntoRequestArguments() {
		$uploadedFiles = array (
			'a0' => array (
				'a1' => array(
					'name' => 'a.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpbqXsYt',
					'error' => 0,
					'size' => 100,
				),
			),
			'd0' => array (
				'd1' => array(
					'd2' => array(
						'd3' => array(
							'name' => 'd.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phprR3fax',
							'error' => 0,
							'size' => 400,
						),
					),
				),
			),
			'e0' => array (
				'e1' => array(
					'e2' => array(
						0 => array(
							'name' => 'e_one.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/php01fitB',
							'error' => 0,
							'size' => 510,
						)
					)
				)
			)
		);

		$postArguments = array(
			'a0' => array('a1POST' => 'postValue'),
			'e0' => array('e1' => array('e2' => 'will be overwritten'))
		);

		$expectedArguments = array (
			'a0' => array (
				'a1POST' => 'postValue',
				'a1' => array (
					'name' => 'a.txt',
					'type' => 'text/plain',
					'tmp_name' => '/private/var/tmp/phpbqXsYt',
					'error' => 0,
					'size' => 100,
				),
			),
			'e0' => array (
				'e1' => array (
					'e2' => array (
						0 => array (
							'name' => 'e_one.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/php01fitB',
							'error' => 0,
							'size' => 510,
						),
					),
				),
			),
			'd0' => array (
				'd1' => array (
					'd2' => array (
						'd3' => array (
							'name' => 'd.txt',
							'type' => 'text/plain',
							'tmp_name' => '/private/var/tmp/phprR3fax',
							'error' => 0,
							'size' => 400,
						)
					)
				)
			)
		);

		$mockRequestUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$mockRequestUri->expects($this->once())->method('getArguments')->will($this->returnValue(array()));

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRawPostArguments')->will($this->returnValue($postArguments));
		$mockEnvironment->expects($this->any())->method('getUploadedFiles')->will($this->returnValue($uploadedFiles));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array('getMethod', 'getRequestUri'), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getRequestUri')->will($this->returnValue($mockRequestUri));
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('POST'));

		$builder = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\ActionRequestBuilder', array('dummy'), array(), '', FALSE);
		$builder->injectEnvironment($mockEnvironment);
		$builder->_call('setArgumentsFromRawRequestData', $mockRequest);

		$this->assertSame($expectedArguments, $mockRequest->getArguments());
	}
}
?>