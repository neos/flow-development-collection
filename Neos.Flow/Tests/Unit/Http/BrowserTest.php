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

use Neos\Flow\Http\Uri;
use Neos\Flow\Http\Client;
use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;

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
    protected function setUp()
    {
        parent::setUp();
        $this->browser = new Client\Browser();
    }

    /**
     * @test
     */
    public function requestingUriQueriesRequestEngine()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->isInstanceOf(Http\Request::class))
            ->will($this->returnValue(new Http\Response()));
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
            ->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue(new Http\Response()));
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->addAutomaticRequestHeader('Content-Type', 'text/plain');
        $this->browser->request('http://localhost/foo');

        $this->assertTrue($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
        $this->assertSame('Acme', $this->browser->getLastRequest()->getHeader('X-Test-Header'));
        $this->assertContains('text/plain', $this->browser->getLastRequest()->getHeader('Content-Type'));
    }

    /**
     * @test
     * @depends automaticHeadersAreSetOnEachRequest
     */
    public function automaticHeadersCanBeRemovedAgain()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->will($this->returnValue(new Http\Response()));
        $this->browser->setRequestEngine($requestEngine);

        $this->browser->addAutomaticRequestHeader('X-Test-Header', 'Acme');
        $this->browser->removeAutomaticRequestHeader('X-Test-Header');
        $this->browser->request('http://localhost/foo');
        $this->assertFalse($this->browser->getLastRequest()->hasHeader('X-Test-Header'));
    }

    /**
     * @test
     */
    public function browserFollowsRedirectionIfResponseTellsSo()
    {
        $initialUri = new Uri('http://localhost/foo');
        $redirectUri = new Uri('http://localhost/goToAnotherFoo');

        $firstResponse = new Http\Response();
        $firstResponse->setStatus(301);
        $firstResponse->setHeader('Location', (string)$redirectUri);
        $secondResponse = new Http\Response();
        $secondResponse->setStatus(202);

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with($this->attributeEqualTo('uri', $initialUri))
            ->will($this->returnValue($firstResponse));
        $requestEngine
            ->expects($this->at(1))
            ->method('sendRequest')
            ->with($this->attributeEqualTo('uri', $redirectUri))
            ->will($this->returnValue($secondResponse));

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request($initialUri);
        $this->assertSame($secondResponse, $actual);
    }

    /**
     * @test
     */
    public function browserDoesNotRedirectOnLocationHeaderButNot3xxResponseCode()
    {
        $twoZeroOneResponse = new Http\Response();
        $twoZeroOneResponse->setStatus(201);
        $twoZeroOneResponse->setHeader('Location', 'http://localhost/createdResource/isHere');

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        $requestEngine
            ->expects($this->once())
            ->method('sendRequest')
            ->will($this->returnValue($twoZeroOneResponse));

        $this->browser->setRequestEngine($requestEngine);
        $actual = $this->browser->request('http://localhost/createSomeResource');
        $this->assertSame($twoZeroOneResponse, $actual);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Http\Client\InfiniteRedirectionException
     */
    public function browserHaltsOnAttemptedInfiniteRedirectionLoop()
    {
        $wildResponses = [];
        $wildResponses[0] = new Http\Response();
        $wildResponses[0]->setStatus(301);
        $wildResponses[0]->setHeader('Location', 'http://localhost/pleaseGoThere');
        $wildResponses[1] = new Http\Response();
        $wildResponses[1]->setStatus(301);
        $wildResponses[1]->setHeader('Location', 'http://localhost/ahNoPleaseRatherGoThere');
        $wildResponses[2] = new Http\Response();
        $wildResponses[2]->setStatus(301);
        $wildResponses[2]->setHeader('Location', 'http://localhost/youNoWhatISendYouHere');
        $wildResponses[3] = new Http\Response();
        $wildResponses[3]->setStatus(301);
        $wildResponses[3]->setHeader('Location', 'http://localhost/ahNoPleaseRatherGoThere');

        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        for ($i=0; $i<=3; $i++) {
            $requestEngine
                ->expects($this->at($i))
                ->method('sendRequest')
                ->will($this->returnValue($wildResponses[$i]));
        }

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/mayThePaperChaseBegin');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Http\Client\InfiniteRedirectionException
     */
    public function browserHaltsOnExceedingMaximumRedirections()
    {
        $requestEngine = $this->createMock(Client\RequestEngineInterface::class);
        for ($i=0; $i<=10; $i++) {
            $response = new Http\Response();
            $response->setHeader('Location', 'http://localhost/this/willLead/you/knowhere/' . $i);
            $response->setStatus(301);
            $requestEngine
                ->expects($this->at($i))
                ->method('sendRequest')
                ->will($this->returnValue($response));
        }

        $this->browser->setRequestEngine($requestEngine);
        $this->browser->request('http://localhost/some/initialRequest');
    }
}
