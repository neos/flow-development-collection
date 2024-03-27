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

use Neos\Flow\Mvc\Routing;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Neos\Flow\Annotations as Flow;

/**
 * Testcase for the MVC Web Routing Routes Class
 */
class AttributeRoutesProviderTest extends UnitTestCase
{
    private ReflectionService|MockObject $mockReflectionService;
    private ObjectManagerInterface|MockObject $mockObjectManager;
    private Routing\AttributeRoutesProvider $annotationRoutesProvider;

    public function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->annotationRoutesProvider = new Routing\AttributeRoutesProvider(
            $this->mockReflectionService,
            $this->mockObjectManager,
            ['Vendor\\Example\\Controller\\*']
        );
    }

    /**
     * @test
     */
    public function noAnnotationsYieldEmptyRoutes(): void
    {
        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(\Neos\Flow\Annotations\Route::class)
            ->willReturn([]);

        $routes = $this->annotationRoutesProvider->getRoutes();
        $this->assertEquals(Routes::empty(), $routes);
    }

    /**
     * @test
     */
    public function routesFromAnnotationAreCreatedWhenClassNamesMatch(): void
    {
        $exampleFqnControllerName = 'Vendor\\Example\\Controller\\ExampleController';
        eval('
        namespace Vendor\Example\Controller;
        class ExampleController extends \Neos\Flow\Mvc\Controller\ActionController {
        }'
        );

        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(Flow\Route::class)
            ->willReturn([$exampleFqnControllerName]);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodsAnnotatedWith')
            ->with($exampleFqnControllerName, Flow\Route::class)
            ->willReturn(['specialAction']);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($exampleFqnControllerName, 'specialAction', Flow\Route::class)
            ->willReturn([
                new Flow\Route(uriPattern: 'my/path'),
                new Flow\Route(
                    uriPattern: 'my/other/path',
                    name: 'specialRoute',
                    httpMethods: ['GET', 'POST'],
                    defaults: ['test' => 'foo']
                )
            ]);

        $this->mockObjectManager->expects($this->once())
            ->method('getCaseSensitiveObjectName')
            ->with($exampleFqnControllerName)
            ->willReturn($exampleFqnControllerName);

        $this->mockObjectManager->expects($this->once())
            ->method('getPackageKeyByObjectName')
            ->with($exampleFqnControllerName)
            ->willReturn('Vendor.Example');

        $expectedRoute1 = new Route();
        $expectedRoute1->setName('Vendor.Example :: Example :: special');
        $expectedRoute1->setUriPattern('my/path');
        $expectedRoute1->setDefaults([
            '@package' => 'Vendor.Example',
            '@subpackage' => null,
            '@controller' => 'Example',
            '@action' => 'special',
            '@format' => 'html',
        ]);

        $expectedRoute2 = new Route();
        $expectedRoute2->setName('Vendor.Example :: Example :: specialRoute');
        $expectedRoute2->setUriPattern('my/other/path');
        $expectedRoute2->setDefaults([
            '@package' => 'Vendor.Example',
            '@subpackage' => null,
            '@controller' => 'Example',
            '@action' => 'special',
            '@format' => 'html',
            'test' => 'foo',
        ]);
        $expectedRoute2->setHttpMethods(['GET', 'POST']);

        $this->assertEquals(
            Routes::create($expectedRoute1, $expectedRoute2),
            $this->annotationRoutesProvider->getRoutes()
        );
    }

    /**
     * @test
     */
    public function annotationsOutsideClassNamesAreIgnored(): void
    {
        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(Flow\Route::class)
            ->willReturn(['Vendor\Other\Controller\ExampleController']);

        $this->assertEquals(Routes::empty(), $this->annotationRoutesProvider->getRoutes());
    }
}
