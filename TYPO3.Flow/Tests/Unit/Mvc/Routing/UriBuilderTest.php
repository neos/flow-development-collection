<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Routing;

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
 * Testcase for the URI Helper
 *
 */
class UriBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Web\SubRequest
	 */
	protected $mockSubRequest;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Web\SubRequest
	 */
	protected $mockSubSubRequest;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Sets up the test case
	 *
	 */
	public function setUp() {
		$this->mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$this->mockSubRequest = $this->getMock('TYPO3\FLOW3\Mvc\Web\SubRequest', array(), array(), '', FALSE);
		$this->mockSubRequest->expects($this->any())->method('getRootRequest')->will($this->returnValue($this->mockRequest));
		$this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockRequest));
		$this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('CurrentNamespace'));
		$this->mockSubSubRequest = $this->getMock('TYPO3\FLOW3\Mvc\Web\SubRequest', array(), array(), '', FALSE);
		$this->mockSubSubRequest->expects($this->any())->method('getRootRequest')->will($this->returnValue($this->mockRequest));
		$this->mockSubSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockSubRequest));
		$this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('CurrentSubNamespace'));
		$environment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$environment->expects($this->any())->method('isRewriteEnabled')->will($this->returnValue(TRUE));

		$this->uriBuilder = new \TYPO3\FLOW3\Mvc\Routing\UriBuilder();
		$this->uriBuilder->injectRouter($this->mockRouter);
		$this->uriBuilder->injectEnvironment($environment);
		$this->uriBuilder->setRequest($this->mockRequest);
	}

	/**
	 * @test
	 */
	public function settersAndGettersWorkAsExpected() {
		$this->uriBuilder
			->reset()
			->setArguments(array('test' => 'arguments'))
			->setSection('testSection')
			->setFormat('TestFormat')
			->setCreateAbsoluteUri(TRUE)
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'));

		$this->assertEquals(array('test' => 'arguments'), $this->uriBuilder->getArguments());
		$this->assertEquals('testSection', $this->uriBuilder->getSection());
		$this->assertEquals('testformat', $this->uriBuilder->getFormat());
		$this->assertEquals(TRUE, $this->uriBuilder->getCreateAbsoluteUri());
		$this->assertEquals(TRUE, $this->uriBuilder->getAddQueryString());
		$this->assertEquals(array('test' => 'addQueryStringExcludeArguments'), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
	}

	/**
	 * @test
	 */
	public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments() {
		$arguments = array('foo' => 'bar', 'additionalParam' => 'additionalValue');
		$controllerArguments = array('foo' => 'overruled', 'baz' => array('TYPO3.FLOW3' => 'fluid'));
		$expectedArguments = array('foo' => 'overruled', 'additionalParam' => 'additionalValue', 'baz' => array('TYPO3.FLOW3' => 'fluid'), '@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->setArguments($arguments);
		$this->uriBuilder->uriFor(NULL, $controllerArguments, 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForOnlySetsActionArgumentIfSpecified() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsControllerFromRequestIfControllerIsNotSet() {
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

		$expectedArguments = array('@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), NULL, 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet() {
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackageKeyFromRequest'));

		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', NULL);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet() {
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackage'));
		$this->mockRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsFormatArgumentIfSpecified() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat');

		$this->uriBuilder->setFormat('SomeFormat');
		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty() {
		$expectedArguments = array(
			'CurrentNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
		);
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestAndCurrentRequestIfNotEmpty() {
		$expectedArguments = array(
			'CurrentNamespace' => array(
				'CurrentSubNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
			)
		);
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace() {
		$expectedArguments = array(
			'CurrentNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
		);
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$mockSubSubRequest = $this->getMockBuilder('TYPO3\FLOW3\Mvc\Web\SubRequest')->disableOriginalConstructor()->getMock();
		$mockSubSubRequest->expects($this->any())->method('getRootRequest')->will($this->returnValue($this->mockRequest));
		$mockSubSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockSubRequest));

		$mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

		$this->uriBuilder->setRequest($mockSubSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault() {
		$expectedArguments = array('Foo' => 'Bar');
		$this->mockRequest->expects($this->never())->method('getArguments');

		$this->uriBuilder->setArguments(array('Foo' => 'Bar'));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet() {
		$expectedArguments = array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled');
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
		$this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('Foo' => 'Overruled'));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsWithRequestArgumentsOfCurrentRequestIfAddQueryStringIsSetAndRequestIsOfTypeSubRequest() {
		$expectedArguments = array('CurrentNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled'));
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('CurrentNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar'))));
		$this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('CurrentNamespace' => array('Foo' => 'Overruled')));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet() {
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
		$this->mockRouter->expects($this->once())->method('resolve')->with(array('Foo' => 'Overruled'))->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('Foo' => 'Overruled'));
		$this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('Some'));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildRemovesSpecifiedQueryParametersInCurrentNamespaceIfArgumentsToBeExcludedFromQueryStringIsSetAndRequestIsOfTypeSubRequest() {
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('CurrentNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar'), 'Some' => 'Retained Arguments From Request')));
		$this->mockRouter->expects($this->once())->method('resolve')->with(array('CurrentNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Retained Arguments From Request'))->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('CurrentNamespace' => array('Foo' => 'Overruled')));
		$this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('Some'));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}


	/**
	 * @test
	 */
	public function buildMergesArgumentsWithRootRequestArgumentsIfRequestIsOfTypeSubRequest() {
		$rootRequestArguments = array('SomeNamespace' => array('Foo' => 'From Request'), 'Foo' => 'Bar', 'Some' => 'Other Argument From Request');
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setArguments(array('Foo' => 'Overruled'));
		$this->uriBuilder->build();

		$expectedArguments = array('SomeNamespace' => array('Foo' => 'From Request'), 'Foo' => 'Overruled', 'Some' => 'Other Argument From Request');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('CurrentNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Other Argument From Request');
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('CurrentNamespace' => array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed'), 'Some' => 'Other Argument From Request')));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setArguments(array('CurrentNamespace' => array('Foo' => 'Overruled')));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest() {
		$expectedArguments = array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Overruled')), 'Some' => 'Other Argument From Request');
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed')), 'Some' => 'Other Argument From Request')));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->setArguments(array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Overruled'))));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest() {
		$expectedArguments = array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Retained Argument From Parent Request'), 'Some' => 'Other Argument From Request');
		$this->mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed'), 'Some' => 'Retained Argument From Parent Request'), 'Some' => 'Other Argument From Request')));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->setArguments(array('CurrentNamespace' => array('CurrentSubNamespace' => array('Foo' => 'Overruled'))));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}


	/**
	 * @test
	 */
	public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@package' => 'RootRequestPackageKey');
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('RootRequestPackageKey'));
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@subpackage' => 'RootRequestSubpackageKey');
		$this->mockRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('RootRequestSubpackageKey'));
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@controller' => 'RootRequestControllerName');
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('RootRequestControllerName'));
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@action' => 'RootRequestActionName');
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('RootRequestActionName'));
		$this->mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAppendsSectionIfSectionIsSpecified() {
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setSection('SomeSection');

		$expectedResult = 'resolvedUri#SomeSection';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildPrependsBaseUriIfCreateAbsoluteUriIsSet() {
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
		$this->mockRequest->expects($this->once())->method('getBaseUri')->will($this->returnValue('BaseUri/'));

		$this->uriBuilder->setCreateAbsoluteUri(TRUE);

		$expectedResult = 'BaseUri/resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildPrependsIndexFileIfRewriteUrlsIsOff() {
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$this->uriBuilder->injectEnvironment($mockEnvironment);

		$expectedResult = 'index.php/resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function resetSetsAllOptionsToTheirDefaultValue() {
		$this->uriBuilder
			->setArguments(array('test' => 'arguments'))
			->setSection('testSection')
			->setFormat('someFormat')
			->setCreateAbsoluteUri(TRUE)
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'));

		$this->uriBuilder->reset();

		$this->assertEquals(array(), $this->uriBuilder->getArguments());
		$this->assertEquals('', $this->uriBuilder->getSection());
		$this->assertEquals('', $this->uriBuilder->getFormat());
		$this->assertEquals(FALSE, $this->uriBuilder->getCreateAbsoluteUri());
		$this->assertEquals(FALSE, $this->uriBuilder->getAddQueryString());
		$this->assertEquals(array(), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
	}

	/**
	 * @test
	 */
	public function setRequestResetsUriBuilder() {
		$uriBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder', array('reset'));
		$uriBuilder->expects($this->once())->method('reset');
		$uriBuilder->setRequest($this->mockRequest);
	}

	/**
	 * @test
	 */
	public function setArgumentsSetsNonPrefixedArgumentsByDefault() {
		$arguments = array(
			'argument1' => 'argument1Value',
			'argument2' => array(
				'argument2a' => 'argument2aValue'
			)
		);
		$this->uriBuilder->setArguments($arguments);
		$expectedResult = $arguments;
		$this->assertEquals($expectedResult, $this->uriBuilder->getArguments());
	}



}
?>