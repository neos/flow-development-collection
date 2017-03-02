<?php
namespace Neos\FluidAdaptor\Tests\Unit\View;

use org\bovigo\vfs\vfsStreamWrapper;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\TemplateView;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\View\TemplatePaths;

/**
 *
 */
class TemplatePathsTest extends UnitTestCase
{
    /**
     * Helper to build mock controller context needed to test expandGenericPathPattern.
     *
     * @param string $packageKey
     * @param string $subPackageKey
     * @param string $controllerName
     * @param string $format
     * @return ControllerContext
     */
    protected function setupMockControllerContextForPathResolving($packageKey, $subPackageKey, $controllerName, $format)
    {
        $controllerObjectName = 'Neos\\' . $packageKey . '\\' . ($subPackageKey != $subPackageKey . '\\' ?: '') . 'Controller\\' . $controllerName . 'Controller';

        $httpRequest = Request::create(new Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->createMock(ActionRequest::class, [], [$httpRequest]);
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
        $mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
        $mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

        /** @var $mockControllerContext ControllerContext */
        $mockControllerContext = $this->createMock(ControllerContext::class, ['getRequest'], [], '', false);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }

    public function expandGenericPathPatternDataProvider()
    {
        return [
            // bubbling controller & subpackage parts and optional format
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                ]
            ],
            // just optional format
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates/',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // just bubbling controller & subpackage parts
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@partialRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Partials/Some/Sub/Package/SomeController/@action.json',
                    'Resources/Private/Partials/Some/Sub/Package/@action.json',
                    'Resources/Private/Partials/Sub/Package/@action.json',
                    'Resources/Private/Partials/Package/@action.json',
                    'Resources/Private/Partials/@action.json',
                ]
            ],
            // layoutRootPath
            [
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => null,
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Layouts/@action.xml',
                    'Resources/Private/Layouts/@action',
                ]
            ],
            // partialRootPath
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => null,
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                ]
            ],
            // optional format as directory name
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => [
                    'Resources/Private/Templates_xml/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates_/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // mandatory format as directory name
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => [
                    'Resources/Private/Templates_json/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // paths must not contain double slashes
            [
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Some/Root/Path/',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Some/Root/Path/SomeController/@action.html',
                    'Some/Root/Path/SomeController/@action',
                    'Some/Root/Path/@action.html',
                    'Some/Root/Path/@action',
                ]
            ],
            // paths must be unique
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => 'foo',
                'expectedResult' => [
                    'foo',
                ]
            ],
            // template fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // template fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/@action.html',
                    'Some/Fallback/Path/Sub/Package/@action.html',
                    'Some/Fallback/Path/Package/@action.html',
                    'Some/Fallback/Path/@action.html',
                ]
            ],
            // partial fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Default/Resources/Path', 'Fallback/'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@subpackage/@controller/@partial.@format',
                'expectedResult' => [
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial.html',
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial',
                    'Fallback/Some/Sub/Package/SomeController/@partial.html',
                    'Fallback/Some/Sub/Package/SomeController/@partial',
                ]
            ],
            // partial fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Default/Resources/Path', 'Fallback1/', 'Fallback2'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@controller/@subpackage/@partial',
                'expectedResult' => [
                    'Default/Resources/Path/SomeController/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Sub/Package/@partial',
                    'Default/Resources/Path/Package/@partial',
                    'Default/Resources/Path/@partial',
                    'Fallback1/SomeController/Some/Sub/Package/@partial',
                    'Fallback1/Some/Sub/Package/@partial',
                    'Fallback1/Sub/Package/@partial',
                    'Fallback1/Package/@partial',
                    'Fallback1/@partial',
                    'Fallback2/SomeController/Some/Sub/Package/@partial',
                    'Fallback2/Some/Sub/Package/@partial',
                    'Fallback2/Sub/Package/@partial',
                    'Fallback2/Package/@partial',
                    'Fallback2/@partial',
                ]
            ],
            // layout fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['foo', 'bar'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Default/Layout/Path', 'Fallback/Path'],
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => [
                    'Default/Layout/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                ]
            ],
            // layout fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Resources/Layouts', 'Some/Fallback/Path'],
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => 'Static/@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => [
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout',
                    'Static/Resources/Layouts/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Sub/Package/@layout',
                    'Static/Resources/Layouts/Package/@layout.html',
                    'Static/Resources/Layouts/Package/@layout',
                    'Static/Resources/Layouts/@layout.html',
                    'Static/Resources/Layouts/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Package/@layout.html',
                    'Static/Some/Fallback/Path/Package/@layout',
                    'Static/Some/Fallback/Path/@layout.html',
                    'Static/Some/Fallback/Path/@layout',
                ]
            ],
            // combined fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Templates', 'Templates/Fallback1', 'Templates/Fallback2'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Resources/Partials'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Resources/Layouts', 'Layouts/Fallback1'],
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@templateRoot/@partialRoot/@subpackage/@controller/foo',
                'expectedResult' => [
                    'Resources/Layouts/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expandGenericPathPatternDataProvider()
     *
     * @param string $package
     * @param string $subPackage
     * @param string $controller
     * @param string $format
     * @param string $templateRootPath
     * @param array $templateRootPaths
     * @param string $partialRootPath
     * @param array $partialRootPaths
     * @param string $layoutRootPath
     * @param array $layoutRootPaths
     * @param boolean $bubbleControllerAndSubpackage
     * @param boolean $formatIsOptional
     * @param string $pattern
     * @param string $expectedResult
     */
    public function expandGenericPathPatternTests($package, $subPackage, $controller, $format, $templateRootPath, array $templateRootPaths = null, $partialRootPath, array $partialRootPaths = null, $layoutRootPath, array $layoutRootPaths = null, $bubbleControllerAndSubpackage, $formatIsOptional, $pattern, $expectedResult)
    {
        $options = [];
        if ($templateRootPath !== null) {
            $options['templateRootPath'] = $templateRootPath;
        }
        if ($templateRootPaths !== null) {
            $options['templateRootPaths'] = $templateRootPaths;
        }

        if ($partialRootPath !== null) {
            $options['partialRootPath'] = $partialRootPath;
        }
        if ($partialRootPaths !== null) {
            $options['partialRootPaths'] = $partialRootPaths;
        }

        if ($layoutRootPath !== null) {
            $options['layoutRootPath'] = $layoutRootPath;
        }
        if ($layoutRootPaths !== null) {
            $options['layoutRootPaths'] = $layoutRootPaths;
        }

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['dummy'], [$options], '', true);
        $patternReplacementVariables = [
            'packageKey' => $package,
            'subPackageKey' => $subPackage,
            'controllerName' => $controller,
            'format' => $format
        ];

        $actualResult = $templatePaths->_call('expandGenericPathPattern', $pattern, $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithBubblingDisabledAndFormatNotOptional()
    {
        $options = [
            'templateRootPaths' => ['Resources/Private/']
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, null, [$options], '', true);

        $expected = ['Resources/Private/Templates/My/@action.html'];
        $actual = $templatePaths->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', [
            'subPackageKey' => null,
            'controllerName' => 'My',
            'format' => 'html'
        ], false, false);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatNotOptional()
    {
        $options = [
            'templateRootPaths' => ['Resources/Private/']
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, null, [$options], '', true);

        $actual = $templatePaths->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', [
            'subPackageKey' => 'MySubPackage',
            'controllerName' => 'My',
            'format' => 'html'
        ], false, false);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatOptional()
    {
        $options = [
            'templateRootPaths' => ['Resources/Private/']
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, null, [$options], '', true);

        $actual = $templatePaths->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', [
            'subPackageKey' => 'MySubPackage',
            'controllerName' => 'My',
            'format' => 'html'
        ], false, true);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingEnabledAndFormatOptional()
    {
        $options = [
            'templateRootPaths' => ['Resources/Private/']
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, null, [$options], '', true);

        $actual = $templatePaths->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', [
            'subPackageKey' => 'MySubPackage',
            'controllerName' => 'My',
            'format' => 'html'
        ], true, true);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action',
            'Resources/Private/Templates/MySubPackage/@action.html',
            'Resources/Private/Templates/MySubPackage/@action',
            'Resources/Private/Templates/@action.html',
            'Resources/Private/Templates/@action'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function pathToPartialIsResolvedCorrectly()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/SomePartial', 'contentsOfSomePartial');

        $paths = [
            'vfs://NonExistentDir/UnknowFile.html',
            'vfs://MyPartials/SomePartial.html',
            'vfs://MyPartials/SomePartial'
        ];

        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [[
            'partialPathAndFilenamePattern' => '@partialRoot/@subpackage/@partial.@format'
        ]], '', true);
        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', ['partial' => 'SomePartial', 'format' => 'html'], true, true)->will($this->returnValue($paths));

        $this->assertSame('contentsOfSomePartial', $templatePaths->getPartialSource('SomePartial'));
    }

    /**
     * @test
     */
    public function getTemplateSourceChecksDifferentPathPatternsAndReturnsTheFirstPathWhichExists()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/@action.html',
            'vfs://MyTemplates/MyCoolAction.html'
        ];

        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'templatePathAndFilenamePattern' => '@templateRoot/@subpackage/@controller/@action.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', [
            'controllerName' => '',
            'action' => 'MyCoolAction',
            'format' => 'html'
        ], false, false)->will($this->returnValue($paths));

        $this->assertSame('contentsOfMyCoolAction', $templatePaths->getTemplateSource('', 'myCoolAction'));
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplatePathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        ];

        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'templatePathAndFilenamePattern' => '@templateRoot/@subpackage/@controller/@action.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', [
            'controllerName' => '',
            'action' => 'MyCoolAction',
            'format' => 'html'
        ], false, false)->will($this->returnValue($paths));

        $templatePaths->getTemplateSource('', 'myCoolAction');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getTemplatePathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        ];

        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'templatePathAndFilenamePattern' => '@templateRoot/@subpackage/@controller/@action.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', [
            'controllerName' => '',
            'action' => 'MyCoolAction',
            'format' => 'html'
        ], false, false)->will($this->returnValue($paths));

        $templatePaths->getTemplateSource('', 'myCoolAction');
    }

    /**
     * @test
     */
    public function resolveTemplatePathAndFilenameReturnsTheExplicitlyConfiguredTemplatePathAndFilename()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['dummy'], [['templatePathAndFilename' => 'vfs://MyTemplates/MyCoolAction.html']]);

        $this->assertSame('contentsOfMyCoolAction', $templatePaths->_call('getTemplateSource'));
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'layoutPathAndFilenamePattern' => '@layoutRoot/@layout.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@layoutRoot/@layout.@format', [
            'layout' => 'Default',
            'format' => 'html'
        ], true, true)->will($this->returnValue($paths));

        $templatePaths->getLayoutSource();
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'layoutPathAndFilenamePattern' => '@layoutRoot/@layout.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@layoutRoot/@layout.@format', [
            'layout' => 'SomeLayout',
            'format' => 'html'
        ], true, true)->will($this->returnValue($paths));

        $templatePaths->getLayoutSource('SomeLayout');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfNoPathCanBeResolved()
    {
        vfsStreamWrapper::register();
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://NonExistentDir/AnotherUnknownFile.html',
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'partialPathAndFilenamePattern' => '@partialRoot/@subpackage/@partial.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', [
            'partial' => 'SomePartial',
            'format' => 'html'
        ], true, true)->will($this->returnValue($paths));

        $templatePaths->getPartialSource('SomePartial');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException
     */
    public function getPartialPathAndFilenameThrowsExceptionIfResolvedPathPointsToADirectory()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates/NotAFile');
        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/NotAFile'
        ];

        /** @var TemplatePaths $templatePaths */
        $templatePaths = $this->getAccessibleMock(TemplatePaths::class, ['expandGenericPathPattern'], [
            [
                'partialPathAndFilenamePattern' => '@partialRoot/@subpackage/@partial.@format'
            ]
        ], '', true);

        $templatePaths->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', [
            'partial' => 'SomePartial',
            'format' => 'html'
        ], true, true)->will($this->returnValue($paths));

        $templatePaths->getPartialSource('SomePartial');
    }
}
