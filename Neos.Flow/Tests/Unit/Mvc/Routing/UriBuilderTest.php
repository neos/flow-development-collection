<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;
use Neos\Utility;

/**
 * Testcase for the URI Helper
 */
class UriBuilderTest extends UnitTestCase
{
    /**
     * @var Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var Mvc\Routing\RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRouter;

    /**
     * @var Http\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockMainRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSubRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSubSubRequest;

    /**
     * Sets up the test case
     *
     */
    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();

        $this->mockRouter = $this->createMock(Mvc\Routing\RouterInterface::class);

        $this->mockMainRequest = $this->createMock(Mvc\ActionRequest::class, [], [$this->mockHttpRequest]);
        $this->mockMainRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockMainRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockMainRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockMainRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(true));
        $this->mockMainRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

        $this->mockSubRequest = $this->createMock(Mvc\ActionRequest::class, [], [$this->mockMainRequest]);
        $this->mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $this->mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubNamespace'));

        $this->mockSubSubRequest = $this->createMock(Mvc\ActionRequest::class, [], [$this->mockSubRequest]);
        $this->mockSubSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $this->mockSubSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $this->mockSubSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $this->mockSubSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockSubRequest));

        $environment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->setMethods(['isRewriteEnabled'])->getMock();
        $environment->expects($this->any())->method('isRewriteEnabled')->will($this->returnValue(true));

        $this->uriBuilder = new Mvc\Routing\UriBuilder();
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
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('TestFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments']);

        $this->assertEquals(['test' => 'arguments'], $this->uriBuilder->getArguments());
        $this->assertEquals('testSection', $this->uriBuilder->getSection());
        $this->assertEquals('testformat', $this->uriBuilder->getFormat());
        $this->assertEquals(true, $this->uriBuilder->getCreateAbsoluteUri());
        $this->assertEquals(true, $this->uriBuilder->getAddQueryString());
        $this->assertEquals(['test' => 'addQueryStringExcludeArguments'], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments()
    {
        $arguments = ['foo' => 'bar', 'additionalParam' => 'additionalValue'];
        $controllerArguments = ['foo' => 'overruled', 'baz' => ['Neos.Flow' => 'fluid']];
        $expectedArguments = ['foo' => 'overruled', 'additionalParam' => 'additionalValue', 'baz' => ['Neos.Flow' => 'fluid'], '@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage'];

        $this->uriBuilder->setArguments($arguments);
        $this->uriBuilder->uriFor('index', $controllerArguments, 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function uriForThrowsExceptionIfActionNameIsNotSpecified()
    {
        $this->uriBuilder->uriFor(null, [], 'SomeController', 'SomePackage');
    }

    /**
     * @test
     */
    public function uriForSetsControllerFromRequestIfControllerIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], null, 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', null);
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet()
    {
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('SomePackage'));
        $this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithExplicitEmptySubpackageKeyDoesNotUseRequestSubpackageKey()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $mockSubRequest */
        $mockSubRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->setMethods([])->setConstructorArgs([$this->mockMainRequest])->getMock();
        $mockSubRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
        $mockSubRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockMainRequest));
        $mockSubRequest->expects($this->any())->method('isMainRequest')->will($this->returnValue(false));
        $mockSubRequest->expects($this->any())->method('getParentRequest')->will($this->returnValue($this->mockMainRequest));
        $mockSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));
        $mockSubRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue('SomeSubpackageKeyFromRequest'));

        $this->uriBuilder->setRequest($mockSubRequest);

        $expectedArguments = ['@action' => 'show', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => ''];

        $this->uriBuilder->uriFor('show', null, 'SomeController', 'SomePackage', '');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsFormatArgumentIfSpecified()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat'];

        $this->uriBuilder->setFormat('SomeFormat');
        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsForMultipleNamespacedSubRequest()
    {
        $expectedArguments = [
            'SubNamespace' => [
                'arg1' => 'val1',
                '@action' => 'someaction',
                '@controller' => 'somecontroller',
                '@package' => 'somepackage',
                'SubSubNamespace' => [
                    'arg1' => 'val1',
                    '@action' => 'someaction',
                    '@controller' => 'somecontroller',
                    '@package' => 'somepackage'
                ]
            ]
        ];
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockSubRequest->expects($this->any())->method('getArguments')->will($this->returnValue([
            'arg1' => 'val1',
            '@action' => 'someaction',
            '@controller' => 'somecontroller',
            '@package' => 'somepackage'
        ]));
        $this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue(''));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault()
    {
        $expectedArguments = ['Foo' => 'Bar'];
        $this->mockMainRequest->expects($this->never())->method('getArguments');

        $this->uriBuilder->setArguments(['Foo' => 'Bar']);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet()
    {
        $expectedArguments = ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled'];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));
        $this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);

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
        $expectedArguments = ['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled']];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']]));
        $this->mockRouter->expects($this->once())->method('resolve')->with($expectedArguments)->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);

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
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue(['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));
        $this->mockRouter->expects($this->once())->method('resolve')->with(['Foo' => 'Overruled'])->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

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
            ->will($this->returnValue(['Some' => 'Retained Arguments From Request']));

        $this->mockSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubNamespace'));

        $this->mockSubRequest->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => ['Arguments' => 'From Request']]));

        $this->mockRouter->expects($this->once())->method('resolve')->with(['SubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Arguments From Request'])->will($this->returnValue('resolvedUri'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

        $expectedResult = 'resolvedUri';
        $actualResult = $this->uriBuilder->build();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRootRequestArgumentsIfRequestIsOfTypeSubRequest()
    {
        $rootRequestArguments = [
            'SomeNamespace' => ['Foo' => 'From Request'],
            'Foo' => 'Bar',
            'Some' => 'Other Argument From Request'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SomeNamespace' => ['Foo' => 'From Request'],
            'Foo' => 'Overruled',
            'Some' => 'Other Argument From Request'
        ];
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesArgumentsBelongingToNamespacedSubRequests()
    {
        $rootRequestArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'Foo' => 'Bar'
        ];
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildKeepsArgumentsBelongingToNamespacedSubRequestsIfAddQueryStringIsSet()
    {
        $rootRequestArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesArgumentsBelongingToNamespacedSubSubRequests()
    {
        $rootRequestArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));
        $this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument'
            ],
            'Foo' => 'Bar'
        ];
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildKeepsArgumentsBelongingToNamespacedSubSubRequestsIfAddQueryStringIsSet()
    {
        $rootRequestArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        $this->mockMainRequest->expects($this->once())->method('getArguments')->will($this->returnValue($rootRequestArguments));
        $this->mockSubSubRequest->expects($this->any())->method('getArgumentNamespace')->will($this->returnValue('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument',
                'SubSubNamespace' => [
                    'SubSub' => 'Argument'
                ]
            ],
            'Foo' => 'Bar'
        ];
        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubNamespace'));

        $this->mockSubSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildWithAddQueryStringMergesAllArgumentsAndKeepsRequestBoundariesIntact()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubNamespace'));

        $this->mockSubRequest->expects($this->once())
            ->method('getArguments')
            ->will($this->returnValue(['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest->expects($this->any())
            ->method('getArgumentNamespace')
            ->will($this->returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(['Foo' => 'SomeArgument']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }


    /**
     * @test
     */
    public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@package' => 'RootRequestPackageKey'];
        $this->mockMainRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('RootRequestPackageKey'));
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@subpackage' => 'RootRequestSubpackageKey'];
        $this->mockMainRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('RootRequestSubpackageKey'));
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@controller' => 'RootRequestControllerName'];
        $this->mockMainRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('RootRequestControllerName'));
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $this->assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@action' => 'RootRequestActionName'];
        $this->mockMainRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('RootRequestActionName'));
        $this->mockMainRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));

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
    public function buildPrependsIndexFileIfRewriteUrlsIsOff()
    {
        $this->mockRouter->expects($this->once())->method('resolve')->will($this->returnValue('resolvedUri'));
        $mockEnvironment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->setMethods(['isRewriteEnabled'])->getMock();
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
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('someFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments']);

        $this->uriBuilder->reset();

        $this->assertEquals([], $this->uriBuilder->getArguments());
        $this->assertEquals('', $this->uriBuilder->getSection());
        $this->assertEquals('', $this->uriBuilder->getFormat());
        $this->assertEquals(false, $this->uriBuilder->getCreateAbsoluteUri());
        $this->assertEquals(false, $this->uriBuilder->getAddQueryString());
        $this->assertEquals([], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function setRequestResetsUriBuilder()
    {
        /** @var Mvc\Routing\UriBuilder|\PHPUnit_Framework_MockObject_MockObject $uriBuilder */
        $uriBuilder = $this->getAccessibleMock(Mvc\Routing\UriBuilder::class, ['reset']);
        $uriBuilder->expects($this->once())->method('reset');
        $uriBuilder->setRequest($this->mockMainRequest);
    }

    /**
     * @test
     */
    public function setArgumentsSetsNonPrefixedArgumentsByDefault()
    {
        $arguments = [
            'argument1' => 'argument1Value',
            'argument2' => [
                'argument2a' => 'argument2aValue'
            ]
        ];
        $this->uriBuilder->setArguments($arguments);
        $expectedResult = $arguments;
        $this->assertEquals($expectedResult, $this->uriBuilder->getArguments());
    }
}
