<?php
namespace Neos\Flow\Tests\Functional\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Session;

class SessionManagementTest extends FunctionalTestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $route = new Route();
        $route->setName('Functional Test - Session::SessionTest');
        $route->setUriPattern('test/session(/{@action})');
        $route->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Session\Fixtures',
            '@controller' => 'SessionTest',
            '@action' => 'sessionStart',
            '@format' =>'html'
        ]);
        $this->router->addRoute($route);
    }

    /**
     * @test
     */
    public function objectManagerAlwaysReturnsTheSameSessionIfInterfaceIsSpecified()
    {
        $session1 = $this->objectManager->get(Session\SessionInterface::class);
        $session2 = $this->objectManager->get(Session\SessionInterface::class);
        $this->assertSame($session1, $session2);
    }

    /**
     * @test
     */
    public function objectManagerAlwaysReturnsANewSessionInstanceIfClassNameIsSpecified()
    {
        $session1 = $this->objectManager->get(Session\Session::class);
        $session2 = $this->objectManager->get(Session\Session::class);
        $this->assertNotSame($session1, $session2);
    }

    /**
     * Checks if getCurrentSessionSession() returns the one and only session which can also
     * be retrieved through Dependency Injection using the SessionInterface.
     *
     * @test
     */
    public function getCurrentSessionReturnsTheCurrentlyActiveSession()
    {
        $injectedSession = $this->objectManager->get(Session\SessionInterface::class);
        $sessionManager = $this->objectManager->get(Session\SessionManagerInterface::class);
        $otherInjectedSession = $this->objectManager->get(Session\SessionInterface::class);

        $retrievedSession = $sessionManager->getCurrentSession();
        $this->assertSame($injectedSession, $retrievedSession);
        $this->assertSame($otherInjectedSession, $retrievedSession);
    }

    /**
     * Makes sure that the functional base testcase initializes an HTTP request and
     * an HTTP response which can be retrieved from the special request handler by
     * the session initialization in order to retrieve or set the session cookie.
     *
     * See bug #43590
     *
     * @test
     */
    public function aSessionCanBeStartedInAFunctionalTest()
    {
        $session = $this->objectManager->get(Session\SessionInterface::class);
        $session->start();
        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * This test makes sure that if a session is used through the HTTP Browser in
     * a functional test, the Session does not have side effects which result, for
     * example, in a cookie sent only at the end of the first request.
     *
     * @test
     */
    public function aSessionUsedInAFunctionalTestVirtualBrowserSendsCookiesOnEachRequest()
    {
        $response = $this->browser->request('http://localhost/test/session');
        $this->assertTrue($response->hasCookie('Flow_Testing_Session'), 'Available Cookies are: ' . implode(', ', array_keys($response->getCookies())));

        $response = $this->browser->request('http://localhost/test/session');
        $this->assertTrue($response->hasCookie('Flow_Testing_Session'), 'Available Cookies are: ' . implode(', ', array_keys($response->getCookies())));
    }
}
