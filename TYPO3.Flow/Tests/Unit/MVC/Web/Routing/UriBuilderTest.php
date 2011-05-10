<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\Web\Routing;

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
 * Testcase for the URI Helper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UriBuilderTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $mockRequest;

	/**
	 * @var \F3\FLOW3\MVC\Web\SubRequest
	 */
	protected $mockSubRequest;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * Sets up the test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->mockRouter = $this->getMock('F3\FLOW3\MVC\Web\Routing\RouterInterface');
		$this->mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->mockSubRequest = $this->getMock('F3\FLOW3\MVC\Web\SubRequest', array(), array(), '', FALSE);
		$this->mockSubRequest->expects($this->any())->method('getRootRequest')->will($this->returnValue($this->mockRequest));
		$this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('CurrentNamespace'));
		$environment = $this->getMock('F3\FLOW3\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$environment->expects($this->any())->method('isRewriteEnabled')->will($this->returnValue(TRUE));

		$this->uriBuilder = new \F3\FLOW3\MVC\Web\Routing\UriBuilder();
		$this->uriBuilder->injectRouter($this->mockRouter);
		$this->uriBuilder->injectEnvironment($environment);
		$this->uriBuilder->setRequest($this->mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments() {
		$arguments = array('foo' => 'bar', 'additionalParam' => 'additionalValue');
		$controllerArguments = array('foo' => 'overruled', 'baz' => array('FLOW3' => 'fluid'));
		$expectedArguments = array('foo' => 'overruled', 'additionalParam' => 'additionalValue', 'baz' => array('FLOW3' => 'fluid'), '@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->setArguments($arguments);
		$this->uriBuilder->uriFor(NULL, $controllerArguments, 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForOnlySetsActionArgumentIfSpecified() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForSetsControllerFromRequestIfControllerIsNotSet() {
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

		$expectedArguments = array('@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), NULL, 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet() {
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackageKeyFromRequest'));

		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', NULL);
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage');

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForSetsFormatArgumentIfSpecified() {
		$expectedArguments = array('@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat');

		$this->uriBuilder->setFormat('SomeFormat');
		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty() {
		$expectedArguments = array(
			'CurrentNamespace' => array('arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage')
		);

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->uriFor('SomeAction', array('arg1' => 'val1'), 'SomeController', 'SomePackage');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@package' => 'RootRequestPackageKey');
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('RootRequestPackageKey'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@subpackage' => 'RootRequestSubpackageKey');
		$this->mockRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('RootRequestSubpackageKey'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@controller' => 'RootRequestControllerName');
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('RootRequestControllerName'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest() {
		$expectedArguments = array('@action' => 'RootRequestActionName');
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('RootRequestActionName'));

		$this->uriBuilder->setRequest($this->mockSubRequest);
		$this->uriBuilder->build();

		$this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildPrependsIndexFileIfRewriteUrlsIsOff() {
		$this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array('isRewriteEnabled'), array(), '', FALSE);
		$this->uriBuilder->injectEnvironment($mockEnvironment);

		$expectedResult = 'index.php/resolvedUri';
		$actualResult = $this->uriBuilder->build();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRequestResetsUriBuilder() {
		$uriBuilder = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Routing\UriBuilder', array('reset'));
		$uriBuilder->expects($this->once())->method('reset');
		$uriBuilder->setRequest($this->mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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