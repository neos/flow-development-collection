<?php

declare(strict_types=1);

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
use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\FluidAdaptor\View\StandaloneView;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;
use TYPO3Fluid\Fluid\Core\Parser\UnknownNamespaceException;

/**
 * Testcase for Standalone View
 */
final class StandaloneViewTest extends FunctionalTestCase
{
    #[Test]
    public function inlineTemplateIsEvaluatedCorrectly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('This is my cool {foo} template!');

        $expected = 'This is my cool bar template!';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function renderSectionIsEvaluatedCorrectly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('Around stuff... <f:section name="innerSection">test {foo}</f:section> after it');

        $expected = 'test bar';
        $actual = $standaloneView->renderSection('innerSection');
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function renderThrowsExceptionIfNeitherTemplateSourceNorTemplatePathAndFilenameAreSpecified(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->render()->getContents();
    }

    #[Test]
    public function renderThrowsExceptionSpecifiedTemplatePathAndFilenameDoesNotExist(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NonExistingTemplate.txt');
        $standaloneView->render()->getContents();
    }

    #[Test]
    public function renderThrowsExceptionIfWrongEnctypeIsSetForFormUpload(): void
    {
        $this->expectException(WrongEnctypeException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithFormUpload.txt');
        $standaloneView->render()->getContents();
    }

    #[Test]
    public function renderThrowsExceptionIfSpecifiedTemplatePathAndFilenamePointsToADirectory(): void
    {
        $this->expectException(InvalidTemplateResourceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures');
        $standaloneView->render()->getContents();
    }

    #[Test]
    public function templatePathAndFilenameIsLoaded(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('name', 'Karsten');
        $standaloneView->assign('name', 'Robert');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplate.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function variablesAreEscapedByDefault(): void
    {
        $standaloneView = new StandaloneView(null);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('Hello {name}.');

        $expected = 'Hello Sebastian &lt;script&gt;alert(&quot;dangerous&quot;);&lt;/script&gt;.';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function variablesAreNotEscapedIfEscapingIsDisabled(): void
    {
        $standaloneView = new StandaloneView(null);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('{escapingEnabled=false}Hello {name}.');

        $expected = 'Hello Sebastian <script>alert("dangerous");</script>.';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function variablesCanBeNested()
    {
        $standaloneView = new StandaloneView(null);
        $standaloneView->assign('type', 'thing');
        $standaloneView->assign('flavor', 'yellow');
        $standaloneView->assign('config', ['thing' => ['value' => ['yellow' => 'Okayish']]]);
        $standaloneView->setTemplateSource('{config.{type}.value.{flavor}}');

        $expected = 'Okayish';
        $actual = $standaloneView->render()->getContents();
        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function partialWithDefaultLocationIsUsedIfNoPartialPathIsSetExplicitly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function explicitPartialPathIsUsed(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');
        $standaloneView->setPartialRootPath(__DIR__ . '/Fixtures/SpecialPartialsDirectory');

        $expected = 'This is a test template. Hello Karsten.';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function layoutWithDefaultLocationIsUsedIfNoLayoutPathIsSetExplicitly(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');

        $expected = 'Hey HEY HO';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function explicitLayoutPathIsUsed(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = 'Hey -- overridden -- HEY HO';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    #[Test]
    public function viewThrowsExceptionWhenUnknownViewHelperIsCalled(): void
    {
        $this->expectException(UnknownNamespaceException::class);
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithUnknownViewHelper.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $standaloneView->render()->getContents();
    }

    #[Test]
    public function xmlNamespacesCanBeIgnored(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithCustomNamespaces.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = '<foo:bar /><bar:foo></bar:foo><foo.bar:baz />foobar';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }

    /**
     * Tests the wrong interceptor behavior described in ticket FLOW-430
     * Basically the rendering should be consistent regardless of cache flushes,
     * but due to the way the interceptor configuration was built the second
     * rendering was bound to fail, this should never happen.
     */
    #[Test]
    public function interceptorsWorkInPartialRenderedInStandaloneSection(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('html');

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('hack', '<h1>HACK</h1>');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt');

        $expected = 'Christian uses &lt;h1&gt;HACK&lt;/h1&gt;';
        $actual = trim($standaloneView->renderSection('test'));
        self::assertSame($expected, $actual, 'First rendering was not escaped.');

        $partialCacheIdentifier = $standaloneView->getTemplatePaths()->getPartialIdentifier('Test');
        $templateCache = $this->objectManager->get(CacheManager::class)->getCache('Fluid_TemplateCache');
        $templateCache->remove($partialCacheIdentifier);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('hack', '<h1>HACK</h1>');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt');

        $expected = 'Christian uses &lt;h1&gt;HACK&lt;/h1&gt;';
        $actual = trim($standaloneView->renderSection('test'));
        self::assertSame($expected, $actual, 'Second rendering was not escaped.');
    }

    #[Test]
    public function settingAndGettingFormatWorksAsExpected(): void
    {
        $formatToBeSet = 'xml';
        $standaloneView = new StandaloneView();
        $standaloneView->setFormat($formatToBeSet);

        self::assertSame($formatToBeSet, $standaloneView->getFormat());
        self::assertSame($formatToBeSet, $standaloneView->getRenderingContext()->getTemplatePaths()->getFormat());
    }

    #[Test]
    public function settingAndGettingTemplatePathAndFilenameWorksAsExpected(): void
    {
        $templatePathAndFilename = __DIR__ . '/Fixtures/NestedRenderingConfiguration/TemplateWithSection.txt';
        $standaloneView = new StandaloneView();
        $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);

        self::assertSame($templatePathAndFilename, $standaloneView->getTemplatePathAndFilename());
    }

    #[Test]
    public function formViewHelpersOutsideOfFormWork(): void
    {
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost');
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest);
        $standaloneView->assign('name', 'Karsten');
        $standaloneView->assign('name', 'Robert');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithFormField.txt');

        $expected = 'This is a test template.<input type="checkbox" name="checkbox-outside" value="1" />';
        $actual = $standaloneView->render()->getContents();
        self::assertSame($expected, $actual);
    }
}
