<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the URI Helper
 *
 */
class UriBuilderTest extends UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $mockHttpRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $mockMainRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $mockSubRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $mockSubSubRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Sets up the test case
	 *
	 */
	public function setUp() {
		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$this->mockRouter = $this->getMock('TYPO3\Flow\Mvc\Routing\RouterInterface');

		$this->mockMainRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array($this->mockHttpRequest));
		$this->mockMainRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockMainRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockMainRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockMainRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(TRUE));
		$this->mockMainRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

		$this->mockSubRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(FALSE));
		$this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubNamespace'));

		$this->mockSubSubRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array($this->mockSubRequest));
		$this->mockSubSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockSubSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockSubSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(FALSE));
		$this->mockSubSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockSubRequest));

		$environment = $this->getMock('TYPO3\Flow\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$environment->expects($this->any())->method('isRewriteEnabled')->will($this->returnValue(TRUE));

		$this->uriBuilder = new \TYPO3\Flow\Mvc\Routing\UriBuilder();
		$this->inject($this->uriBuilder, 'router', $this->mockRouter);
		$this->inject($this->uriBuilder, 'environment', $environment);
		$this->uriBuilder->setRequest($this->mockMainRequest);
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
		$controllerArguments = array('foo' => 'overruled', 'baz' => array('TYPO3.Flow' => 'fluid'));
		$expectedArguments = array('foo' => 'overruled', 'additionalParam' => 'additionalValue', 'baz' => array('TYPO3.Flow' => 'fluid'), '@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->setArguments($arguments);
		$this->uriBuilder->uriFor('index', $controllerArguments, 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Routing\Exception\MissingActionNameException
	 */
	public function uriForThrowsExceptionIfActionNameIsNotSpecified() {
		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
	}

	/**
	 * @test
	 */
	public function uriForSetsControllerFromRequestIfControllerIsNotSet() {
		$this->mockMainRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

		$expectedArguments = array('@action' => 'index', '@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage');

		$this->uriBuilder->uriFor('index', array(), NULL, 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet() {
		$this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackageKeyFromRequest'));

		$expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest');

		$this->uriBuilder->uriFor('index', array(), 'SomeController', NULL);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet() {
		$this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackage'));
		$this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

		$expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest');

		$this->uriBuilder->uriFor('index', array(), 'SomeController');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet() {
		$expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->uriFor('index', array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForInSubRequestWithExplicitEmptySubpackageKeyDoesNotUseRequestSubpackageKey() {
		$this->mockSubRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
		$this->mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(FALSE));
		$this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
		$this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));
		$this->mockSubRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

		$this->uriBuilder->setRequest($this->mockSubRequest);

		$expectedArguments = array('@action' => 'show', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => '');

		$this->uriBuilder->uriFor('show', NULL, 'SomeController', 'SomePackage', '');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsFormatArgumentIfSpecified() {
		$expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat');

		$this->uriBuilder->setFormat('SomeFormat');
		$this->uriBuilder->uriFor('index', array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty() {
		$expectedArguments = array(
			'SubNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
		);
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsForMultipleNamespacedSubRequest() {
		$expectedArguments = array(
			'SubNamespace' => array(
				'arg1' => 'val1',
				'@action' => 'someaction',
				'@controller' => 'somecontroller',
				'@package' => 'somepackage',
				'SubSubNamespace' => array(
					'arg1' => 'val1',
					'@action' => 'someaction',
					'@controller' => 'somecontroller',
					'@package' => 'somepackage'
				)
			)
		);
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));
		$this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array(
			'arg1' => 'val1',
			'@action' => 'someaction',
			'@controller' => 'somecontroller',
			'@package' => 'somepackage'
		)));
		$this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace() {
		$expectedArguments = array(
			'SubNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
		);
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault() {
		$expectedArguments = array('Foo' => 'Bar');
		$this->mockMainRequest->expects($this->never())->method('getArguments');

		$this->uriBuilder->setArguments(array('Foo' => 'Bar'));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet() {
		$expectedArguments = array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled');
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
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
		$expectedArguments = array('SubNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled'));
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('SubNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar'))));
		$this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('SubNamespace' => array('Foo' => 'Overruled')));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet() {
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
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
		$this->mockMainRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Some' => 'Retained Arguments From Request')));

		$this->mockSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubNamespace'));

		$this->mockSubRequest->expects($this->any())
			->method('getArguments')
			->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'))));

		$this->mockRouter->expects($this->once())->method('resolve')->with(array('SubNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Retained Arguments From Request'))->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArguments(array('SubNamespace' => array('Foo' => 'Overruled')));
		$this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('Some'));

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsWithRootRequestArgumentsIfRequestIsOfTypeSubRequest() {
		$rootRequestArguments = array(
			'SomeNamespace' => array('Foo' => 'From Request'),
			'Foo' => 'Bar',
			'Some' => 'Other Argument From Request'
		);
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setArguments(array('Foo' => 'Overruled'));
		$this->uriBuilder->build();

		$expectedArguments = array(
			'SomeNamespace' => array('Foo' => 'From Request'),
			'Foo' => 'Overruled',
			'Some' => 'Other Argument From Request'
		);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildRemovesArgumentsBelongingToNamespacedSubRequests() {
		$rootRequestArguments = array(
			'SubNamespace' => array('Sub' => 'Argument'),
			'Foo' => 'Bar'
		);
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$expectedArguments = array(
			'Foo' => 'Bar'
		);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildKeepsArgumentsBelongingToNamespacedSubRequestsIfAddQueryStringIsSet() {
		$rootRequestArguments = array(
			'SubNamespace' => array('Sub' => 'Argument'),
			'Foo' => 'Bar'
		);
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE)->build();

		$expectedArguments = array(
			'SubNamespace' => array('Sub' => 'Argument'),
			'Foo' => 'Bar'
		);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildRemovesArgumentsBelongingToNamespacedSubSubRequests() {
		$rootRequestArguments = array(
			'SubNamespace' => array(
				'Sub' => 'Argument',
				'SubSubNamespace' => array(
					'SubSub' => 'Argument'
				)
			),
			'Foo' => 'Bar'
		);
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));
		$this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->build();

		$expectedArguments = array(
			'SubNamespace' => array(
				'Sub' => 'Argument'
			),
			'Foo' => 'Bar'
		);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildKeepsArgumentsBelongingToNamespacedSubSubRequestsIfAddQueryStringIsSet() {
		$rootRequestArguments = array(
			'SubNamespace' => array(
				'Sub' => 'Argument',
				'SubSubNamespace' => array(
					'SubSub' => 'Argument'
				)
			),
			'Foo' => 'Bar'
		);
		$this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));
		$this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->setAddQueryString(TRUE)->build();

		$expectedArguments = array(
			'SubNamespace' => array(
				'Sub' => 'Argument',
				'SubSubNamespace' => array(
					'SubSub' => 'Argument'
				)
			),
			'Foo' => 'Bar'
		);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('SubNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Other Argument From Request');

		$this->mockMainRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Some' => 'Other Argument From Request')));

		$this->mockSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubNamespace'));

		$this->mockSubRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed')));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->setArguments(array('SubNamespace' => array('Foo' => 'Overruled')));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest() {
		$expectedArguments = array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled')), 'Some' => 'Other Argument From Request');

		$this->mockMainRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Some' => 'Other Argument From Request')));

		$this->mockSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubNamespace'));

		$this->mockSubSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubSubNamespace'));

		$this->mockSubSubRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed')));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->setArguments(array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled'))));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest() {
		$expectedArguments = array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Retained Argument From Parent Request'), 'Some' => 'Other Argument From Request');
		$this->mockMainRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Some' => 'Other Argument From Request')));

		$this->mockSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubNamespace'));

		$this->mockSubRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Some' => 'Retained Argument From Parent Request')));

		$this->mockSubSubRequest->expects($this->any())
			->method('getArgumentNamespace')
			->will($this->returnValue('SubSubNamespace'));

		$this->mockSubSubRequest->expects($this->once())
			->method('getArguments')
			->will($this->returnValue(array('Foo' => 'Should be overridden', 'Bar' => 'Should be removed')));

		$this->uriBuilder->setRequest($this->mockSubSubRequest);
		$this->uriBuilder->setArguments(array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled'))));
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}


	/**
	 * @test
	 */
	public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@package' => 'RootRequestPackageKey');
		$this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('RootRequestPackageKey'));
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@subpackage' => 'RootRequestSubpackageKey');
		$this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('RootRequestSubpackageKey'));
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@controller' => 'RootRequestControllerName');
		$this->mockMainRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('RootRequestControllerName'));
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 */
	public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@action' => 'RootRequestActionName');
		$this->mockMainRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('RootRequestActionName'));
		$this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

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
		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getBaseUri')->will($this->returnValue('http://www.domain.tld/document-root/'));
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setCreateAbsoluteUri(TRUE);

		$expectedResult = 'http://www.domain.tld/document-root/resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildPrependsScriptRequestPathByDefaultIfCreateAbsoluteUriIsFalse() {
		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getScriptRequestPath')->will($this->returnValue('/document-root/'));
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setCreateAbsoluteUri(FALSE);

		$expectedResult = '/document-root/resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildDoesNotPrependsScriptRequestPathIfCreateRelativePathsCompatibilityFlagIsTrue() {
		$this->mockHttpRequest->expects($this->never())->method('getScriptRequestPath');
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

		$this->uriBuilder->setCreateAbsoluteUri(FALSE);
		$this->uriBuilder->setCreateRelativePaths(TRUE);

		$expectedResult = 'resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildPrependsIndexFileIfRewriteUrlsIsOff() {
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
		$mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$this->inject($this->uriBuilder, 'environment', $mockEnvironment);

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
		$uriBuilder = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\UriBuilder', array('reset'));
		$uriBuilder->expects($this->once())->method('reset');
		$uriBuilder->setRequest($this->mockMainRequest);
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
