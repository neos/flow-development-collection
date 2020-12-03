<?php
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
use Neos\Flow\Tests\UnitTestCase;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the Http Cookie class
 */
class BrowserTest extends UnitTestCase
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
        $this->browser = new Client\Browser();
        $this->inject($this->browser, 'serverRequestFactory', new ServerRequestFactory(new UriFactory()));
    }

    /**
     * @test
     */
    public function requestingUriQueriesRequestEngine()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects(self::once())
            ->method('sendRequest')
            ->with($this->isInstanceOf(RequestInterface::class))
            ->will(self::returnValue(new Response()));
        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/foo');
    }

    /**
     * @test
     */
    public function automaticHeadersAreSetOnEachRequest()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects(self::any())
            ->method('sendRequest')
            ->willReturn(new Response());
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->addAutomaticRequestHeader('Content-Type', 'text/plain');
        $this->browser->request('http://localhost/foo');

        self::assertTrue($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
        self::assertSame('Acme', $this->browser->getLastRequest()->getHeaderLine('X-Test-Header'));
        self::assertStringContainsString('text/plain', $this->browser->getLastRequest()->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     * @depends automaticHeadersAreSetOnEachRequest
     */
    public function automaticHeadersCanBeRemovedAgain()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects(self::once())
            ->method('sendRequest')
            ->will(self::returnValue(new Response()));
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->removeAutomaticRequestHeader('X-Test-Header');
        $this->browser->request('http://localhost/foo');
        self::assertFalse($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
    }

    /**
     * @test
     */
    public function browserFollowsRedirectionIfResponseTellsSo()
    {
        $initialUri = new Uri('http://localhost/foo');
        $redirectUri = new Uri('http://localhost/goToAnotherFoo');

        $firstResponse = new Response(301, ['Location' => (string)$redirectUri]);
        $secondResponse = new Response(202);

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->method('sendRequest')
            ->withConsecutive([
                self::callback(function (ServerRequestInterface $request) use ($initialUri) {
                    return (string)$request->getUri() === (string)$initialUri;
                })
            ], [
                self::callback(function (ServerRequestInterface $request) use ($redirectUri) {
                    return (string)$request->getUri() === (string)$redirectUri;
                })
            ])->willReturnOnConsecutiveCalls($firstResponse, $secondResponse);

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request($initialUri);
        self::assertSame($secondResponse, $actual);
    }

    /**
     * @test
     */
    public function browserDoesNotRedirectOnLocationHeaderButNot3xxResponseCode()
    {
        $twoZeroOneResponse = new Response(201, ['Location' => 'http://localhost/createdResource/isHere']);

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects(self::once())
            ->method('sendRequest')
            ->will(self::returnValue($twoZeroOneResponse));

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request('http://localhost/createSomeResource');
        self::assertSame($twoZeroOneResponse, $actual);
    }

    /**
     * @test
     */
    public function browserHaltsOnAttemptedInfiniteRedirectionLoop()
    {
        $this->expectException(Client\InfiniteRedirectionException::class);
        $wildResponses = [];
        $wildResponses[0] = new Response(301, ['Location' => 'http://localhost/pleaseGoThere']);
        $wildResponses[1] = new Response(301, ['Location' => 'http://localhost/ahNoPleaseRatherGoThere']);
        $wildResponses[2] = new Response(301, ['Location' => 'http://localhost/youNoWhatISendYouHere']);
        $wildResponses[3] = new Response(301, ['Location' => 'http://localhost/ahNoPleaseRatherGoThere']);

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        for ($i=0; $i<=3; $i++) {
            $requestEngine
                ->expects(self::exactly(count($wildResponses)))
                ->method('sendRequest')
                ->willReturnOnConsecutiveCalls(...$wildResponses);
        }

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/mayThePaperChaseBegin');
    }

    /**
     * @test
     */
    public function browserHaltsOnExceedingMaximumRedirections()
    {
        $this->expectException(Client\InfiniteRedirectionException::class);
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $responses = [];
        for ($i=0; $i<=10; $i++) {
            $responses[] = new Response(301, ['Location' => 'http://localhost/this/willLead/you/knowhere/' . $i]);
        }
        $requestEngine
            ->expects(self::exactly(count($responses)))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(...$responses);

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/some/initialRequest');
    }
}
