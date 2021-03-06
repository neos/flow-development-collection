<?php
namespace Neos\Flow\Tests\Functional\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the HTTP browser
 */
class BrowserTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerRoute(
            'Functional Test - Http::Client::BrowserTest',
            'test/http/redirecting(/{@action})',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Http\Fixtures',
                '@controller' => 'Redirecting',
                '@action' => 'fromHere',
                '@format' => 'html'
            ]
        );
        $this->registerRoute(
            'Functional Test - Http::Client::BrowserTest',
            'test/http/request(/{@action})',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Http\Fixtures',
                '@controller' => 'Request',
                '@action' => 'index',
                '@format' => 'html'
            ]
        );
    }

    /**
     * @test
     */
    public function argumentsAreSentAsRequestBodyEvenInGetRequest()
    {
        $response = $this->browser->request('http://localhost/test/http/request/body', 'GET', ['foo' => 'bar']);
        self::assertEquals(json_encode(['foo' => 'bar']), $response->getBody()->getContents());
    }

    /**
     * Check if the browser can handle redirects
     *
     * @test
     */
    public function redirectsAreFollowed()
    {
        $response = $this->browser->request('http://localhost/test/http/redirecting');
        self::assertEquals('arrived.', $response->getBody()->getContents());
    }

    /**
     * Check if the browser doesn't follow redirects if told so
     *
     * @test
     */
    public function redirectsAreNotFollowedIfSwitchedOff()
    {
        $this->browser->setFollowRedirects(false);
        $response = $this->browser->request('http://localhost/test/http/redirecting');
        self::assertStringNotContainsString('arrived.', $response->getBody()->getContents());
        self::assertEquals(303, $response->getStatusCode());
        self::assertEquals('http://localhost/test/http/redirecting/tohere', $response->getHeaderLine('Location'));
    }
}
