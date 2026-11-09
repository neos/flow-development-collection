<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Client;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Http\Client\RequestEngineInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the Http Cookie class
 */
final class BrowserTest extends UnitTestCase
{
    /**
     * @var Client\Browser
     */
    protected $browser;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->browser = new Browser();
        $this->inject($this->browser, 'serverRequestFactory', new ServerRequestFactory(new UriFactory()));
    }

    #[Test]
    public function requestingUriQueriesRequestEngine()
    {
        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn((new Response()));
        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/foo');
    }

    #[Test]
    public function automaticHeadersAreSetOnEachRequest()
    {
        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $requestEngine
            ->method('sendRequest')
            ->willReturn(new Response());
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->addAutomaticRequestHeader('Content-Type', 'text/plain');
        $this->browser->request('http://localhost/foo');

        self::assertTrue($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
        self::assertSame('Acme', $this->browser->getLastRequest()->getHeaderLine('X-Test-Header'));
        self::assertStringContainsString('text/plain', (string) $this->browser->getLastRequest()->getHeaderLine('Content-Type'));
    }

    #[Depends('automaticHeadersAreSetOnEachRequest')]
    #[Test]
    public function automaticHeadersCanBeRemovedAgain()
    {
        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn((new Response()));
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->removeAutomaticRequestHeader('X-Test-Header');
        $this->browser->request('http://localhost/foo');
        self::assertFalse($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
    }

    #[Test]
    public function browserFollowsRedirectionIfResponseTellsSo()
    {
        $initialUri = new Uri('http://localhost/foo');
        $redirectUri = new Uri('http://localhost/goToAnotherFoo');

        $firstResponse = new Response(301, ['Location' => (string)$redirectUri]);
        $secondResponse = new Response(202);

        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $matcher = $this->exactly(2);
        $requestEngine->expects($matcher)
            ->method('sendRequest')->willReturnCallback(function (...$parameters) use ($matcher, $initialUri, $redirectUri, $firstResponse, $secondResponse) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertInstanceOf(ServerRequestInterface::class, $parameters[0]);
                    self::assertSame((string)$initialUri, (string)$parameters[0]->getUri());
                    return $firstResponse;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertInstanceOf(ServerRequestInterface::class, $parameters[0]);
                    self::assertSame((string)$redirectUri, (string)$parameters[0]->getUri());
                    return $secondResponse;
                }
            });

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request($initialUri);
        self::assertSame($secondResponse, $actual);
    }

    #[Test]
    public function browserDoesNotRedirectOnLocationHeaderButNot3xxResponseCode()
    {
        $twoZeroOneResponse = new Response(201, ['Location' => 'http://localhost/createdResource/isHere']);

        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(($twoZeroOneResponse));

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request('http://localhost/createSomeResource');
        self::assertSame($twoZeroOneResponse, $actual);
    }

    #[Test]
    public function browserHaltsOnAttemptedInfiniteRedirectionLoop()
    {
        $this->expectException(InfiniteRedirectionException::class);
        $wildResponses = [];
        $wildResponses[0] = new Response(301, ['Location' => 'http://localhost/pleaseGoThere']);
        $wildResponses[1] = new Response(301, ['Location' => 'http://localhost/ahNoPleaseRatherGoThere']);
        $wildResponses[2] = new Response(301, ['Location' => 'http://localhost/youNoWhatISendYouHere']);
        $wildResponses[3] = new Response(301, ['Location' => 'http://localhost/ahNoPleaseRatherGoThere']);

        $requestEngine = $this->createMock(RequestEngineInterface::class);
        for ($i = 0; $i <= 3; $i++) {
            $requestEngine
                ->expects($this->exactly(count($wildResponses)))
                ->method('sendRequest')
                ->willReturnOnConsecutiveCalls(...$wildResponses);
        }

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/mayThePaperChaseBegin');
    }

    #[Test]
    public function browserHaltsOnExceedingMaximumRedirections()
    {
        $this->expectException(InfiniteRedirectionException::class);
        $requestEngine = $this->createMock(RequestEngineInterface::class);
        $responses = [];
        for ($i = 0; $i <= 10; $i++) {
            $responses[] = new Response(301, ['Location' => 'http://localhost/this/willLead/you/knowhere/' . $i]);
        }
        $requestEngine
            ->expects($this->exactly(count($responses)))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(...$responses);

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/some/initialRequest');
    }
}
