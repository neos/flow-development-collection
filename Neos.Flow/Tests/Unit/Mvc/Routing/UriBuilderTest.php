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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;
use Neos\Flow\Utility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

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
     * @var Mvc\Routing\RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRouter;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockBaseUri;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockMainRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSubRequest;

    /**
     * @var Mvc\ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSubSubRequest;

    /**
     * Sets up the test case
     *
     */
    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();




        $this->mockBaseUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockBaseUriProvider = $this->createMock(Http\BaseUriProvider::class);
        $this->mockBaseUriProvider->expects(self::any())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn($this->mockBaseUri);

        $this->mockRouter = $this->createMock(Mvc\Routing\RouterInterface::class);

        $this->mockMainRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockMainRequest->expects(self::any())->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockMainRequest->expects(self::any())->method('getParentRequest')->willReturn(null);
        $this->mockMainRequest->expects(self::any())->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockMainRequest->expects(self::any())->method('isMainRequest')->willReturn(true);
        $this->mockMainRequest->expects(self::any())->method('getArgumentNamespace')->willReturn('');

        $this->mockSubRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockSubRequest->expects(self::any())->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockSubRequest->expects(self::any())->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockSubRequest->expects(self::any())->method('isMainRequest')->willReturn(false);
        $this->mockSubRequest->expects(self::any())->method('getParentRequest')->willReturn($this->mockMainRequest);
        $this->mockSubRequest->expects(self::any())->method('getArgumentNamespace')->willReturn('SubNamespace');

        $this->mockSubSubRequest = $this->createMock(Mvc\ActionRequest::class);
        $this->mockSubSubRequest->expects(self::any())->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockSubSubRequest->expects(self::any())->method('getMainRequest')->willReturn($this->mockMainRequest);
        $this->mockSubSubRequest->expects(self::any())->method('isMainRequest')->willReturn(false);
        $this->mockSubSubRequest->expects(self::any())->method('getParentRequest')->willReturn($this->mockSubRequest);

        $environment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->setMethods(['isRewriteEnabled'])->getMock();
        $environment->expects(self::any())->method('isRewriteEnabled')->will(self::returnValue(true));

        $this->uriBuilder = new Mvc\Routing\UriBuilder();
        $this->inject($this->uriBuilder, 'router', $this->mockRouter);
        $this->inject($this->uriBuilder, 'environment', $environment);
        $this->inject($this->uriBuilder, 'baseUriProvider', $this->mockBaseUriProvider);
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

        self::assertEquals(['test' => 'arguments'], $this->uriBuilder->getArguments());
        self::assertEquals('testSection', $this->uriBuilder->getSection());
        self::assertEquals('testformat', $this->uriBuilder->getFormat());
        self::assertEquals(true, $this->uriBuilder->getCreateAbsoluteUri());
        self::assertEquals(true, $this->uriBuilder->getAddQueryString());
        self::assertEquals(['test' => 'addQueryStringExcludeArguments'], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
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
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForThrowsExceptionIfActionNameIsNotSpecified()
    {
        $this->expectException(Mvc\Routing\Exception\MissingActionNameException::class);
        $this->uriBuilder->uriFor('', [], 'SomeController', 'SomePackage');
    }

    /**
     * @test
     */
    public function uriForSetsControllerFromRequestIfControllerIsNotSet()
    {
        $this->mockMainRequest->expects(self::once())->method('getControllerName')->will(self::returnValue('SomeControllerFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontrollerfromrequest', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], null, 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsPackageKeyFromRequestIfPackageKeyIsNotSet()
    {
        $this->mockMainRequest->expects(self::once())->method('getControllerPackageKey')->will(self::returnValue('SomePackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', null);
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsSubpackageKeyFromRequestIfPackageKeyAndSubpackageKeyAreNotSet()
    {
        $this->mockMainRequest->expects(self::once())->method('getControllerPackageKey')->will(self::returnValue('SomePackage'));
        $this->mockMainRequest->expects(self::once())->method('getControllerSubpackageKey')->will(self::returnValue('SomeSubpackageKeyFromRequest'));

        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => 'somesubpackagekeyfromrequest'];

        $this->uriBuilder->uriFor('index', [], 'SomeController');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForDoesNotUseSubpackageKeyFromRequestIfOnlyThePackageIsSet()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage'];

        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithExplicitEmptySubpackageKeyDoesNotUseRequestSubpackageKey()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $mockSubRequest */
        $mockSubRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->setMethods([])->disableOriginalConstructor()->getMock();
        $mockSubRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($this->mockHttpRequest));
        $mockSubRequest->expects(self::any())->method('getMainRequest')->will(self::returnValue($this->mockMainRequest));
        $mockSubRequest->expects(self::any())->method('isMainRequest')->will(self::returnValue(false));
        $mockSubRequest->expects(self::any())->method('getParentRequest')->will(self::returnValue($this->mockMainRequest));
        $mockSubRequest->expects(self::any())->method('getArgumentNamespace')->will(self::returnValue(''));
        $mockSubRequest->expects(self::any())->method('getControllerSubpackageKey')->will(self::returnValue('SomeSubpackageKeyFromRequest'));

        $this->uriBuilder->setRequest($mockSubRequest);

        $expectedArguments = ['@action' => 'show', '@controller' => 'somecontroller', '@package' => 'somepackage', '@subpackage' => ''];

        $this->uriBuilder->uriFor('show', [], 'SomeController', 'SomePackage', '');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForSetsFormatArgumentIfSpecified()
    {
        $expectedArguments = ['@action' => 'index', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'someformat'];

        $this->uriBuilder->setFormat('SomeFormat');
        $this->uriBuilder->uriFor('index', [], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceIfNotEmpty()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
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
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));
        $this->mockSubRequest->expects(self::any())->method('getArguments')->will(self::returnValue([
            'arg1' => 'val1',
            '@action' => 'someaction',
            '@controller' => 'somecontroller',
            '@package' => 'somepackage'
        ]));
        $this->mockSubSubRequest->expects(self::any())->method('getArgumentNamespace')->will(self::returnValue('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForPrefixesControllerArgumentsWithSubRequestArgumentNamespaceOfParentRequestIfCurrentRequestHasNoNamespace()
    {
        $expectedArguments = [
            'SubNamespace' => ['arg1' => 'val1', '@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->mockSubSubRequest->expects(self::any())->method('getArgumentNamespace')->will(self::returnValue(''));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->uriFor('SomeAction', ['arg1' => 'val1'], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeArgumentsWithRequestArgumentsByDefault()
    {
        $expectedArguments = ['Foo' => 'Bar'];
        $this->mockMainRequest->expects(self::never())->method('getArguments');

        $this->uriBuilder->setArguments(['Foo' => 'Bar']);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsIfAddQueryStringIsSet()
    {
        $expectedArguments = ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled'];
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue(['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));

        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);

        $this->uriBuilder->build();
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsWithRequestArgumentsOfCurrentRequestIfAddQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Overruled']];
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue(['SubNamespace' => ['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']]));

        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);

        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet()
    {
        $expectedArguments = ['Foo' => 'Overruled'];
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue(['Some' => ['Arguments' => 'From Request'], 'Foo' => 'Bar']));

        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildRemovesSpecifiedQueryParametersInCurrentNamespaceIfArgumentsToBeExcludedFromQueryStringIsSetAndRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['Some' => 'Retained Arguments From Request', 'SubNamespace' => ['Foo' => 'Overruled']];
        $this->mockMainRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Retained Arguments From Request']));

        $this->mockSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubNamespace'));

        $this->mockSubRequest->expects(self::any())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => ['Arguments' => 'From Request']]));

        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedArguments) {
            self::assertSame($expectedArguments, $resolveContext->getRouteValues());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->setArgumentsToBeExcludedFromQueryString(['Some']);

        $this->uriBuilder->build();
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
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['Foo' => 'Overruled']);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SomeNamespace' => ['Foo' => 'From Request'],
            'Foo' => 'Overruled',
            'Some' => 'Other Argument From Request'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
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
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
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
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue($rootRequestArguments));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setAddQueryString(true)->build();

        $expectedArguments = [
            'SubNamespace' => ['Sub' => 'Argument'],
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
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
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue($rootRequestArguments));
        $this->mockSubSubRequest->expects(self::any())->method('getArgumentNamespace')->will(self::returnValue('SubSubNamespace'));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->build();

        $expectedArguments = [
            'SubNamespace' => [
                'Sub' => 'Argument'
            ],
            'Foo' => 'Bar'
        ];
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
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
        $this->mockMainRequest->expects(self::once())->method('getArguments')->will(self::returnValue($rootRequestArguments));
        $this->mockSubSubRequest->expects(self::any())->method('getArgumentNamespace')->will(self::returnValue('SubSubNamespace'));

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
        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubNamespace'));

        $this->mockSubRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['Foo' => 'Overruled']]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildDoesNotMergeRootRequestArgumentsWithTheCurrentArgumentNamespaceIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']], 'Some' => 'Other Argument From Request'];

        $this->mockMainRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubNamespace'));

        $this->mockSubSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildMergesArgumentsOfTheParentRequestIfRequestIsOfTypeSubRequestAndHasAParentSubRequest()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubNamespace'));

        $this->mockSubRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Foo' => 'Should be overridden', 'Bar' => 'Should be removed']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildWithAddQueryStringMergesAllArgumentsAndKeepsRequestBoundariesIntact()
    {
        $expectedArguments = ['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled'], 'Some' => 'Retained Argument From Parent Request'], 'Some' => 'Other Argument From Request'];
        $this->mockMainRequest->expects(self::any())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Other Argument From Request']));

        $this->mockSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubNamespace'));

        $this->mockSubRequest->expects(self::once())
            ->method('getArguments')
            ->will(self::returnValue(['Some' => 'Retained Argument From Parent Request']));

        $this->mockSubSubRequest->expects(self::any())
            ->method('getArgumentNamespace')
            ->will(self::returnValue('SubSubNamespace'));

        $this->mockSubSubRequest->expects(self::any())
            ->method('getArguments')
            ->will(self::returnValue(['Foo' => 'SomeArgument']));

        $this->uriBuilder->setRequest($this->mockSubSubRequest);
        $this->uriBuilder->setArguments(['SubNamespace' => ['SubSubNamespace' => ['Foo' => 'Overruled']]]);
        $this->uriBuilder->setAddQueryString(true);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }


    /**
     * @test
     */
    public function buildAddsPackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@package' => 'RootRequestPackageKey'];
        $this->mockMainRequest->expects(self::once())->method('getControllerPackageKey')->will(self::returnValue('RootRequestPackageKey'));
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsSubpackageKeyFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@subpackage' => 'RootRequestSubpackageKey'];
        $this->mockMainRequest->expects(self::once())->method('getControllerSubpackageKey')->will(self::returnValue('RootRequestSubpackageKey'));
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsControllerNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@controller' => 'RootRequestControllerName'];
        $this->mockMainRequest->expects(self::once())->method('getControllerName')->will(self::returnValue('RootRequestControllerName'));
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildAddsActionNameFromRootRequestIfRequestIsOfTypeSubRequest()
    {
        $expectedArguments = ['@action' => 'RootRequestActionName'];
        $this->mockMainRequest->expects(self::once())->method('getControllerActionName')->will(self::returnValue('RootRequestActionName'));
        $this->mockMainRequest->expects(self::any())->method('getArguments')->will(self::returnValue([]));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->build();

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function buildPassesBaseUriToRouter()
    {
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame($this->mockBaseUri, $resolveContext->getBaseUri());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildAppendsSectionIfSectionIsSpecified()
    {
        $mockResolvedUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $mockResolvedUri->expects(self::once())->method('withFragment')->with('SomeSection')->will(self::returnValue($mockResolvedUri));

        $this->mockRouter->expects(self::once())->method('resolve')->will(self::returnValue($mockResolvedUri));

        $this->uriBuilder->setSection('SomeSection');
        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildDoesNotSetAbsoluteUriFlagByDefault()
    {
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertFalse($resolveContext->isForceAbsoluteUri());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildForwardsForceAbsoluteUriFlagToRouter()
    {
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertTrue($resolveContext->isForceAbsoluteUri());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setCreateAbsoluteUri(true);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildPrependsScriptRequestPathByDefaultIfCreateAbsoluteUriIsFalse()
    {
        $this->mockHttpRequest->expects(self::atLeastOnce())->method('getServerParams')->willReturn(['SCRIPT_NAME' => '/document-root/index.php']);
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame('document-root/', $resolveContext->getUriPathPrefix());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setCreateAbsoluteUri(false);

        $this->uriBuilder->build();
    }

    /**
     * @test
     */
    public function buildPrependsIndexFileIfRewriteUrlsIsOff()
    {
        $mockEnvironment = $this->getMockBuilder(Utility\Environment::class)->disableOriginalConstructor()->setMethods(['isRewriteEnabled'])->getMock();
        $this->inject($this->uriBuilder, 'environment', $mockEnvironment);

        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            self::assertSame('index.php/', $resolveContext->getUriPathPrefix());
            return $this->getMockBuilder(UriInterface::class)->getMock();
        });

        $this->uriBuilder->setCreateAbsoluteUri(false);

        $this->uriBuilder->build();
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

        self::assertEquals([], $this->uriBuilder->getArguments());
        self::assertEquals('', $this->uriBuilder->getSection());
        self::assertEquals('', $this->uriBuilder->getFormat());
        self::assertEquals(false, $this->uriBuilder->getCreateAbsoluteUri());
        self::assertEquals(false, $this->uriBuilder->getAddQueryString());
        self::assertEquals([], $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
    }

    /**
     * @test
     */
    public function setRequestResetsUriBuilder()
    {
        /** @var Mvc\Routing\UriBuilder|\PHPUnit\Framework\MockObject\MockObject $uriBuilder */
        $uriBuilder = $this->getAccessibleMock(Mvc\Routing\UriBuilder::class, ['reset']);
        $uriBuilder->expects(self::once())->method('reset');
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
        self::assertEquals($expectedResult, $this->uriBuilder->getArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWillKeepFormatOfMainRequest()
    {
        $expectedArguments = [
            '@format' => 'custom',
            'SubNamespace' => ['@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage']
        ];
        $this->mockMainRequest->expects(self::any())->method('getFormat')->will(self::returnValue('custom'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->uriFor('SomeAction', [], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }

    /**
     * @test
     */
    public function uriForInSubRequestWithFormatWillNotOverrideFormatOfMainRequest()
    {
        $expectedArguments = [
            '@format' => 'custom',
            'SubNamespace' => ['@action' => 'someaction', '@controller' => 'somecontroller', '@package' => 'somepackage', '@format' => 'inner']
        ];
        $this->mockMainRequest->expects(self::any())->method('getFormat')->will(self::returnValue('custom'));

        $this->uriBuilder->setRequest($this->mockSubRequest);
        $this->uriBuilder->setFormat('inner');
        $this->uriBuilder->uriFor('SomeAction', [], 'SomeController', 'SomePackage');

        self::assertEquals($expectedArguments, $this->uriBuilder->getLastArguments());
    }
}
