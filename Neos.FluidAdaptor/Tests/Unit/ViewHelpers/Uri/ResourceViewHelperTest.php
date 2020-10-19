<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Service;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use Neos\FluidAdaptor\ViewHelpers\Uri\ResourceViewHelper;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test case for the resource uri view helper
 */
class ResourceViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var ResourceViewHelper
     */
    protected $viewHelper;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockI18nService;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockResourceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockI18nService = $this->createMock(Service::class);
        $this->mockResourceManager = $this->createMock(ResourceManager::class);
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->objectManagerMock->expects(self::any())->method('get')->will($this->returnValueMap([
            [Service::class, $this->mockI18nService],
            [ResourceManager::class, $this->mockResourceManager]
        ]));
        $this->viewHelper = $this->getAccessibleMock(ResourceViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->renderingContext->injectObjectManager($this->objectManagerMock);
    }

    /**
     * @test
     */
    public function renderUsesCurrentControllerPackageKeyToBuildTheResourceUri()
    {
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css')->will(self::returnValue('TheCorrectResourceUri'));
        $this->request->expects(self::atLeastOnce())->method('getControllerPackageKey')->will(self::returnValue('ThePackageKey'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'Styles/Main.css',
            'package' => null,
            'resource' => null,
            'localize' => false
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderUsesCustomPackageKeyIfSpecified()
    {
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css')->will(self::returnValue('TheCorrectResourceUri'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'Styles/Main.css',
            'package' => 'ThePackageKey',
            'resource' => null,
            'localize' => false
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderUsesProvidedPersistentResourceInsteadOfPackageAndPath()
    {
        $resource = new PersistentResource();
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPersistentResourceUri')->with($resource)->will(self::returnValue('TheCorrectResourceUri'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => null,
            'package' => null,
            'resource' => $resource,
            'localize' => false
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderCreatesASpecialBrokenResourceUriIfTheResourceCouldNotBePublished()
    {
        $resource = new PersistentResource();
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPersistentResourceUri')->with($resource)->will(self::returnValue(false));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => null,
            'package' => null,
            'resource' => $resource,
            'localize' => false
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('404-Resource-Not-Found', $resourceUri);
    }

    /**
     * @test
     */
    public function renderLocalizesResource()
    {
        $this->mockI18nService->expects(self::once())->method('getLocalizedFilename')->with('resource://ThePackageKey/Public/Styles/Main.css')->will(self::returnValue(['resource://ThePackageKey/Public/Styles/Main.css.de', new Locale('de')]));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css.de')->will(self::returnValue('TheCorrectResourceUri'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'Styles/Main.css',
            'package' => 'ThePackageKey'
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderLocalizesResourceGivenAsResourceUri()
    {
        $this->mockResourceManager
            ->expects(self::once())
            ->method('getPackageAndPathByPublicPath')
            ->with('resource://ThePackageKey/Public/Styles/Main.css')
            ->will(self::returnValue(['ThePackageKey', 'Styles/Main.css']));
        $this->mockI18nService
            ->expects(self::once())
            ->method('getLocalizedFilename')
            ->with('resource://ThePackageKey/Public/Styles/Main.css')
            ->will(self::returnValue(['resource://ThePackageKey/Public/Styles/Main.de.css', new Locale('de')]));
        $this->mockResourceManager->expects(self::atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.de.css')->will(self::returnValue('TheCorrectResourceUri'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'resource://ThePackageKey/Public/Styles/Main.css',
            'package' => null,
            'resource' => null,
            'localize' => true
        ]);
        $resourceUri = $this->viewHelper->render();
        self::assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderSkipsLocalizationIfRequested()
    {
        $this->mockI18nService->expects(self::never())->method('getLocalizedFilename');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'foo',
            'package' => 'SomePackage',
            'resource' => null,
            'localize' => false
        ]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSkipsLocalizationForResourcesGivenAsResourceUriIfRequested()
    {
        $this->mockI18nService->expects(self::never())->method('getLocalizedFilename');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'resource://SomePackage/Public/Images/foo.jpg',
            'package' => null,
            'resource' => null,
            'localize' => false
        ]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfNeitherResourceNorPathWereGiven()
    {
        $this->expectException(InvalidVariableException::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => null,
            'package' => 'SomePackage',
            'resource' => null
        ]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfResourceUriNotPointingToPublicWasGivenAsPath()
    {
        $this->expectException(InvalidVariableException::class);
        $this->mockResourceManager
            ->expects(self::once())
            ->method('getPackageAndPathByPublicPath')
            ->with('resource://Some.Package/Private/foobar.txt')
            ->willThrowException(new Exception());
        $this->viewHelper = $this->prepareArguments($this->viewHelper, [
            'path' => 'resource://Some.Package/Private/foobar.txt',
            'package' => 'SomePackage'
        ]);
        $this->viewHelper->render();
    }
}
