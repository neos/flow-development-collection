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
class AnnotationRoutesProviderTest extends UnitTestCase
{
    private ReflectionService|MockObject $mockReflectionService;
    private ObjectManagerInterface|MockObject $mockObjectManager;
    private Routing\AnnotationRoutesProvider $annotationRoutesProvider;

    public function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->annotationRoutesProvider = new Routing\AnnotationRoutesProvider(
            $this->mockReflectionService,
            $this->mockObjectManager
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
    public function routesFromAnnotationAreCreated(): void
    {
        $this->mockReflectionService->expects($this->once())
            ->method('getClassesContainingMethodsAnnotatedWith')
            ->with(Flow\Route::class)
            ->willReturn(['Vendor\Example\Controller\ExampleController']);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodsAnnotatedWith')
            ->with('Vendor\Example\Controller\ExampleController', Flow\Route::class)
            ->willReturn(['specialAction']);

        $this->mockReflectionService->expects($this->once())
            ->method('getMethodAnnotations')
            ->with('Vendor\Example\Controller\ExampleController', 'specialAction', Flow\Route::class)
            ->willReturn([
                new Flow\Route(uriPattern: 'my/path'),
                new Flow\Route(uriPattern: 'my/other/path', name: 'specialRoute', defaults: ['test' => 'foo'], httpMethods: ['get', 'post'])
            ]);

        $this->mockObjectManager->expects($this->once())
            ->method('getCaseSensitiveObjectName')
            ->with('Vendor\Example\Controller\ExampleController')
            ->willReturn('Vendor\Example\Controller\ExampleController');

        $this->mockObjectManager->expects($this->once())
            ->method('getPackageKeyByObjectName')
            ->with('Vendor\Example\Controller\ExampleController')
            ->willReturn('Vendor.Example');

        $expectedRoute1 = new Route();
        $expectedRoute1->setUriPattern('my/path');
        $expectedRoute1->setDefaults([
            '@package' => 'Vendor.Example',
            '@subpackage' => null,
            '@controller' => 'Example',
            '@action' => 'special',
            '@format' => 'html'
        ]);

        $expectedRoute2 = new Route();
        $expectedRoute2->setName('specialRoute');
        $expectedRoute2->setUriPattern('my/other/path');
        $expectedRoute2->setDefaults([
            '@package' => 'Vendor.Example',
            '@subpackage' => null,
            '@controller' => 'Example',
            '@action' => 'special',
            '@format' => 'html',
            'test' => 'foo'
        ]);
        $expectedRoute2->setHttpMethods(['get', 'post']);

        $this->assertEquals(
            Routes::create($expectedRoute1, $expectedRoute2),
            $this->annotationRoutesProvider->getRoutes()
        );
    }
}
