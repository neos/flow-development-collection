<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\RouterInterface;
use TYPO3\Flow\Mvc\Routing\UriBuilder;

/**
 * Testcase for the URI Helper
 *
 */
class UriBuilderTest extends UnitTestCase
{
    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRouter;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockMainRequest;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSubRequest;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSubSubRequest;

    /**
     * Sets up the test case
     *
     */
    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $this->mockRouter = $this->createMock(\TYPO3\Flow\Mvc\Routing\RouterInterface::class);

        $this->mockMainRequest = $this->createMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array($this->mockHttpRequest));
        $this->mockMainRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockMainRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockMainRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockMainRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(true));
        $this->mockMainRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

        $this->mockSubRequest = $this->createMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array($this->mockMainRequest));
        $this->mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubNamespace'));

        $this->mockSubSubRequest = $this->createMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array($this->mockSubRequest));
        $this->mockSubSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockSubSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $this->mockSubSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockSubRequest));

        $environment = $this->getMockBuilder(\TYPO3\Flow\Utility\Environment::class)->disableOriginalConstructor()->setMethods(array('isRewriteEnabled'))->getMock();
        $environment->expects($this->any())->method('isRewriteEnabled')->will($this->returnValue(true));

        $this->uriBuilder = new UriBuilder();
        $this->inject($this->uriBuilder, 'router', $this->mockRouter);
        $this->inject($this->uriBuilder, 'environment', $environment);
        $this->uriBuilder->setRequest($this->mockMainRequest);
    }

    /**
     * @test
     */
    public function settersAndGettersWorkAsExpected()
    {
        $this->uriBuilder
            ->reset()
            ->setArguments(array('test' => 'arguments'))
            ->setSection('testSection')
            ->setFormat('TestFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'));

        $this->assertEquals(array('test' => 'arguments'), $this->uriBuilder->getArguments());
        $this->assertEquals('testSection', $this->uriBuilder->getSection());
        $this->assertEquals('testformat', $this->uriBuilder->getFormat());
        $this->assertEquals(true, $this->uriBuilder->getCreateAbsoluteUri());
        $this->assertEquals(true, $this->uriBuilder->getAddQueryString());
        $this->assertEquals(array('test' => 'addQueryStringExcludeArguments'), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments()
    {
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
    public function uriForThrowsExceptionIfActionNameIsNotSpecified()
    {
        $this->uriBuilder->uriFor(null, array(), 'SomeController', 'SomePackage');
    }

    /**
     * @test
     */
    public function uriForSetsControllerFromRequestIfControllerIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

        $expectedArguments = array('@action' => 'index', '@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage');

        $this->uriBuilder->uriFor('index', array(), null, 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackageKeyFromRequest'));

        $expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest');

        $this->uriBuilder->uriFor('index', array(), 'SomeController', null);
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackage'));
        $this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

        $expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest');

        $this->uriBuilder->uriFor('index', array(), 'SomeController');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet()
    {
        $expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage');

        $this->uriBuilder->uriFor('index', array(), 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithExplicitEmptySubpackageKeyDoesNotUseRequestSubpackageKey()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $mockSubRequest */
        $mockSubRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->setMethods(array())->setConstructorArgs(array($this->mockMainRequest))->getMock();
        $mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
        $mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));
        $mockSubRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

        $this->uriBuilder->setRequest($mockSubRequest);

        $expectedArguments = array('@action' => 'show', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => '');

        $this->uriBuilder->uriFor('show', null, 'SomeController', 'SomePackage', '');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsFormatArgumentIfSpecified()
    {
        $expectedArguments = array('@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat');

        $this->uriBuilder->setFormat('SomeFormat');
        $this->uriBuilder->uriFor('index', array(), 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty()
    {
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
    public function uriForPrefixesControllerArgumentsForMultipleNamespacedSubRequest()
    {
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
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace()
    {
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
    public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault()
    {
        $expectedArguments = array('Foo' => 'Bar');
        $this->mockMainRequest->expects($this->never())->method('getArguments');

        $this->uriBuilder->setArguments(array('Foo' => 'Bar'));
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet()
    {
        $expectedArguments = array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled');
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
        $this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(array('Foo' => 'Overruled'));

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsOfCurrentRequestIfAddQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
        $expectedArguments = array('SubNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Overruled'));
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('SubNamespace' => array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar'))));
        $this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(array('SubNamespace' => array('Foo' => 'Overruled')));

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('Some' => array('Arguments' => 'From Request'), 'Foo' => 'Bar')));
        $this->mockRouter->expects($this->once())->method('resolve')->with(array('Foo' => 'Overruled'))->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(array('Foo' => 'Overruled'));
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('Some'));

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersInCurrentNamespaceIfArgumentsToBeExcludedFromQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
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
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(array('SubNamespace' => array('Foo' => 'Overruled')));
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('Some'));

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRootRequestArgumentsIfRequestIsOfTypeSubRequest()
    {
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
    public function buildRemovesArgumentsBelongingToNamespacedSubRequests()
    {
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
    public function buildKeepsArgumentsBelongingToNamespacedSubRequestsIfAddQueryStringIsSet()
    {
        $rootRequestArguments = array(
            'SubNamespace' => array('Sub' => 'Argument'),
            'Foo' => 'Bar'
        );
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = array(
            'SubNamespace' => array('Sub' => 'Argument'),
            'Foo' => 'Bar'
        );
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesArgumentsBelongingToNamespacedSubSubRequests()
    {
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
    public function buildKeepsArgumentsBelongingToNamespacedSubSubRequestsIfAddQueryStringIsSet()
    {
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
        $this->uriBuilder->setAddQueryString(true)->build();

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
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest()
    {
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
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
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
    public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
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
    public function buildWithAddQueryStringMergesAllArgumentsAndKeepsRequestBoundariesIntact()
    {
        $expectedArguments = array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled'), 'Some' => 'Retained Argument From Parent Request'), 'Some' => 'Other Argument From Request');
        $this->mockMainRequest->expects($this->any())
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

        $this->mockSubSubRequest->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array('Foo' => 'SomeArgument')));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(array('SubNamespace' => array('SubSubNamespace' => array('Foo' => 'Overruled'))));
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }


    /**
     * @test
     */
    public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
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
    public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
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
    public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
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
    public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
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
    public function buildAppendsSectionIfSectionIsSpecified()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setSection('SomeSection');

        $expectedResult = 'resolvedUri#SomeSection';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildPrependsBaseUriIfCreateAbsoluteUriIsSet()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getBaseUri')->will($this->returnValue('http://www.domain.tld/document-root/'));
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setCreateAbsoluteUri(true);

        $expectedResult = 'http://www.domain.tld/document-root/resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildPrependsScriptRequestPathByDefaultIfCreateAbsoluteUriIsFalse()
    {
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getScriptRequestPath')->will($this->returnValue('/document-root/'));
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setCreateAbsoluteUri(false);

        $expectedResult = '/document-root/resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildDoesNotPrependsScriptRequestPathIfCreateRelativePathsCompatibilityFlagIsTrue()
    {
        $this->mockHttpRequest->expects($this->never())->method('getScriptRequestPath');
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setCreateAbsoluteUri(false);
        $this->uriBuilder->setCreateRelativePaths(true);

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildPrependsIndexFileIfRewriteUrlsIsOff()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
        $mockEnvironment = $this->getMockBuilder(\TYPO3\Flow\Utility\Environment::class)->disableOriginalConstructor()->setMethods(array('isRewriteEnabled'))->getMock();
        $this->inject($this->uriBuilder, 'environment', $mockEnvironment);

        $expectedResult = 'index.php/resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function resetSetsAllOptionsToTheirDefaultValue()
    {
        $this->uriBuilder
            ->setArguments(array('test' => 'arguments'))
            ->setSection('testSection')
            ->setFormat('someFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'));

        $this->uriBuilder->reset();

        $this->assertEquals(array(), $this->uriBuilder->getArguments());
        $this->assertEquals('', $this->uriBuilder->getSection());
        $this->assertEquals('', $this->uriBuilder->getFormat());
        $this->assertEquals(false, $this->uriBuilder->getCreateAbsoluteUri());
        $this->assertEquals(false, $this->uriBuilder->getAddQueryString());
        $this->assertEquals(array(), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function setRequestResetsUriBuilder()
    {
        /** @var UriBuilder|\PHPUnit_Framework_MockObject_MockObject $uriBuilder */
        $uriBuilder = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Routing\UriBuilder::class, array('reset'));
        $uriBuilder->expects($this->once())->method('reset');
        $uriBuilder->setRequest($this->mockMainRequest);
    }

    /**
     * @test
     */
    public function setArgumentsSetsNonPrefixedArgumentsByDefault()
    {
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
