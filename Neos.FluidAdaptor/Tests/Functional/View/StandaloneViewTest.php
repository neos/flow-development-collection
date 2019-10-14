<?php
namespace Neos\FluidAdaptor\Tests\Functional\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\WrongEnctypeException;
use Neos\FluidAdaptor\Tests\Functional\View\Fixtures\View\StandaloneView;
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use TYPO3Fluid\Fluid\Core\Parser\UnknownNamespaceException;

/**
 * Testcase for Standalone View
 */
class StandaloneViewTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    protected $standaloneViewNonce = '42';

    /**
     * Every testcase should run *twice*. First, it is run in *uncached* way, second,
     * it is run *cached*. To make sure that the first run is always uncached, the
     * $standaloneViewNonce is initialized to some random value which is used inside
     * an overridden version of StandaloneView::createIdentifierForFile.
     */
    public function runBare(): void
    {
        $this->standaloneViewNonce = uniqid('', true);
        parent::runBare();
        $numberOfAssertions = $this->getNumAssertions();
        parent::runBare();
        $this->addToAssertionCount($numberOfAssertions);
    }

    /**
     * @test
     */
    public function inlineTemplateIsEvaluatedCorrectly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('This is my cool {foo} template!');

        $expected = 'This is my cool bar template!';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderSectionIsEvaluatedCorrectly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('Around stuff... <f:section name="innerSection">test {foo}</f:section> after it');

        $expected = 'test bar';
        $actual = $standaloneView->renderSection('innerSection');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfNeitherTemplateSourceNorTemplatePathAndFilenameAreSpecified(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionSpecifiedTemplatePathAndFilenameDoesNotExist(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NonExistingTemplate.txt');
        $standaloneView->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfWrongEnctypeIsSetForFormUpload(): void
    {
        $this->expectException(WrongEnctypeException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithFormUpload.txt');
        $standaloneView->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfSpecifiedTemplatePathAndFilenamePointsToADirectory(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures');
        $standaloneView->render();
    }

    /**
     * @test
     */
    public function templatePathAndFilenameIsLoaded(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Karsten');
        $standaloneView->assign('name', 'Robert');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplate.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function variablesAreEscapedByDefault(): void
    {
        $standaloneView = new StandaloneView(null, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('Hello {name}.');

        $expected = 'Hello Sebastian &lt;script&gt;alert(&quot;dangerous&quot;);&lt;/script&gt;.';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function variablesAreNotEscapedIfEscapingIsDisabled(): void
    {
        $standaloneView = new StandaloneView(null, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('{escapingEnabled=false}Hello {name}.');

        $expected = 'Hello Sebastian <script>alert("dangerous");</script>.';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function partialWithDefaultLocationIsUsedIfNoPartialPathIsSetExplicitly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function explicitPartialPathIsUsed(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');
        $standaloneView->setPartialRootPath(__DIR__ . '/Fixtures/SpecialPartialsDirectory');

        $expected = 'This is a test template. Hello Karsten.';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function layoutWithDefaultLocationIsUsedIfNoLayoutPathIsSetExplicitly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');

        $expected = 'Hey HEY HO';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function explicitLayoutPathIsUsed(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = 'Hey -- overridden -- HEY HO';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function viewThrowsExceptionWhenUnknownViewHelperIsCalled(): void
    {
        $this->expectException(UnknownNamespaceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new Fixtures\View\StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithUnknownViewHelper.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $standaloneView->render();
    }

    /**
     * @test
     */
    public function xmlNamespacesCanBeIgnored(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new Fixtures\View\StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithCustomNamespaces.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = '<foo:bar /><bar:foo></bar:foo><foo.bar:baz />foobar';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }

    /**
     * Tests the wrong interceptor behavior described in ticket FLOW-430
     * Basically the rendering should be consistent regardless of cache flushes,
     * but due to the way the interceptor configuration was build the second second
     * rendering was bound to fail, this should never happen.
     *
     * @test
     */
    public function interceptorsWorkInPartialRenderedInStandaloneSection(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('html');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('hack', '<h1>HACK</h1>');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt');

        $expected = 'Christian uses &lt;h1&gt;HACK&lt;/h1&gt;';
        $actual = trim($standaloneView->renderSection('test'));
        self::assertSame($expected, $actual, 'First rendering was not escaped.');

        $partialCacheIdentifier = $standaloneView->getTemplatePaths()->getPartialIdentifier('Test');
        $templateCache = $this->objectManager->get(CacheManager::class)->getCache('Fluid_TemplateCache');
        $templateCache->remove($partialCacheIdentifier);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('hack', '<h1>HACK</h1>');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt');

        $expected = 'Christian uses &lt;h1&gt;HACK&lt;/h1&gt;';
        $actual = trim($standaloneView->renderSection('test'));
        self::assertSame($expected, $actual, 'Second rendering was not escaped.');
    }

    /**
     * @test
     */
    public function settingAndGettingFormatWorksAsExpected(): void
    {
        $formatToBeSet = 'xml';
        $standaloneView = new StandaloneView();
        $standaloneView->setFormat($formatToBeSet);

        self::assertSame($formatToBeSet, $standaloneView->getFormat());
        self::assertSame($formatToBeSet, $standaloneView->getRenderingContext()->getTemplatePaths()->getFormat());
    }

    /**
     * @test
     */
    public function settingAndGettingTemplatePathAndFilenameWorksAsExpected(): void
    {
        $templatePathAndFilename = __DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt';
        $standaloneView = new StandaloneView();
        $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);

        self::assertSame($templatePathAndFilename, $standaloneView->getTemplatePathAndFilename());
    }

    /**
     * @test
     */
    public function formViewHelpersOutsideOfFormWork(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Karsten');
        $standaloneView->assign('name', 'Robert');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithFormField.txt');

        $expected = 'This is a test template.<input type="checkbox" name="checkbox-outside" value="1" />';
        $actual = $standaloneView->render();
        self::assertSame($expected, $actual);
    }
}
