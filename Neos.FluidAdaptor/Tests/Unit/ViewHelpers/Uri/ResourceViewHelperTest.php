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
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockI18nService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResourceManager;

    public function setUp()
    {
        parent::setUp();
        $this->mockResourceManager = $this->createMock(ResourceManager::class);
        $this->mockI18nService = $this->createMock(Service::class);

        $this->viewHelper = $this->getAccessibleMock(ResourceViewHelper::class, array('renderChildren'), array(), '', false);
        $this->inject($this->viewHelper, 'resourceManager', $this->mockResourceManager);
        $this->inject($this->viewHelper, 'i18nService', $this->mockI18nService);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderUsesCurrentControllerPackageKeyToBuildTheResourceUri()
    {
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css')->will($this->returnValue('TheCorrectResourceUri'));
        $this->request->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('ThePackageKey'));
        $resourceUri = $this->viewHelper->render('Styles/Main.css', null, null, false);
        $this->assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderUsesCustomPackageKeyIfSpecified()
    {
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css')->will($this->returnValue('TheCorrectResourceUri'));
        $resourceUri = $this->viewHelper->render('Styles/Main.css', 'ThePackageKey', null, false);
        $this->assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderUsesProvidedPersistentResourceInsteadOfPackageAndPath()
    {
        $resource = new PersistentResource();
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPersistentResourceUri')->with($resource)->will($this->returnValue('TheCorrectResourceUri'));
        $resourceUri = $this->viewHelper->render(null, null, $resource, false);
        $this->assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderCreatesASpecialBrokenResourceUriIfTheResourceCouldNotBePublished()
    {
        $resource = new PersistentResource();
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPersistentResourceUri')->with($resource)->will($this->returnValue(false));
        $resourceUri = $this->viewHelper->render(null, null, $resource, false);
        $this->assertEquals('404-Resource-Not-Found', $resourceUri);
    }

    /**
     * @test
     */
    public function renderLocalizesResource()
    {
        $this->mockI18nService->expects($this->once())->method('getLocalizedFilename')->with('resource://ThePackageKey/Public/Styles/Main.css')->will($this->returnValue(array('resource://ThePackageKey/Public/Styles/Main.css.de', new Locale('de'))));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.css.de')->will($this->returnValue('TheCorrectResourceUri'));
        $resourceUri = $this->viewHelper->render('Styles/Main.css', 'ThePackageKey');
        $this->assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderLocalizesResourceGivenAsResourceUri()
    {
        $this->mockResourceManager
            ->expects($this->once())
            ->method('getPackageAndPathByPublicPath')
            ->with('resource://ThePackageKey/Public/Styles/Main.css')
            ->will($this->returnValue(['ThePackageKey', 'Styles/Main.css']));
        $this->mockI18nService
            ->expects($this->once())
            ->method('getLocalizedFilename')
            ->with('resource://ThePackageKey/Public/Styles/Main.css')
            ->will($this->returnValue(array('resource://ThePackageKey/Public/Styles/Main.de.css', new Locale('de'))));
        $this->mockResourceManager->expects($this->atLeastOnce())->method('getPublicPackageResourceUri')->with('ThePackageKey', 'Styles/Main.de.css')->will($this->returnValue('TheCorrectResourceUri'));
        $resourceUri = $this->viewHelper->render('resource://ThePackageKey/Public/Styles/Main.css');
        $this->assertEquals('TheCorrectResourceUri', $resourceUri);
    }

    /**
     * @test
     */
    public function renderSkipsLocalizationIfRequested()
    {
        $this->mockI18nService->expects($this->never())->method('getLocalizedFilename');
        $this->viewHelper->render('foo', 'SomePackage', null, false);
    }

    /**
     * @test
     */
    public function renderSkipsLocalizationForResourcesGivenAsResourceUriIfRequested()
    {
        $this->mockI18nService->expects($this->never())->method('getLocalizedFilename');
        $this->viewHelper->render('resource://SomePackage/Public/Images/foo.jpg', null, null, false);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function renderThrowsExceptionIfNeitherResourceNorPathWereGiven()
    {
        $this->viewHelper->render(null, 'SomePackage', null);
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function renderThrowsExceptionIfResourceUriNotPointingToPublicWasGivenAsPath()
    {
        $this->mockResourceManager
            ->expects($this->once())
            ->method('getPackageAndPathByPublicPath')
            ->with('resource://Some.Package/Private/foobar.txt')
            ->willThrowException(new Exception());
        $this->viewHelper->render('resource://Some.Package/Private/foobar.txt', 'SomePackage');
    }
}
