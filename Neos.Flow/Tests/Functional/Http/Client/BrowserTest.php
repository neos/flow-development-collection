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
    public function setUp()
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
    }

    /**
     * Check if the browser can handle redirects
     *
     * @test
     */
    public function redirectsAreFollowed()
    {
        $response = $this->browser->request('http://localhost/test/http/redirecting');
        $this->assertEquals('arrived.', $response->getContent());
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
        $this->assertNotContains('arrived.', $response->getContent());
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals('http://localhost/test/http/redirecting/tohere', $response->getHeader('Location'));
    }
}
