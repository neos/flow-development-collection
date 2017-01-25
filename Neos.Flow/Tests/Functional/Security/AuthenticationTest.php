<?php
namespace Neos\Flow\Tests\Functional\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for Authentication
 */
class AuthenticationTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $accountRepository = $this->objectManager->get(AccountRepository::class);
        $accountFactory = $this->objectManager->get(AccountFactory::class);

        $account = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', ['Neos.Flow:Administrator'], 'TestingProvider');
        $accountRepository->add($account);
        $account2 = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', ['Neos.Flow:Administrator'], 'HttpBasicTestingProvider');
        $accountRepository->add($account2);
        $account3 = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', ['Neos.Flow:Administrator'], 'UsernamePasswordTestingProvider');
        $accountRepository->add($account3);
        $this->persistenceManager->persistAll();

        $route = new Route();
        $route->setName('Functional Test - Security::Restricted');
        $route->setUriPattern('test/security/restricted(/{@action})');
        $route->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'Restricted',
            '@action' => 'public',
            '@format' =>'html'
        ]);
        $route->setAppendExceedingArguments(true);
        $this->router->addRoute($route);

        $route2 = new Route();
        $route2->setName('Functional Test - Security::Authentication');
        $route2->setUriPattern('test/security/authentication(/{@action})');
        $route2->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'Authentication',
            '@action' => 'authenticate',
            '@format' => 'html'
        ]);
        $route2->setAppendExceedingArguments(true);
        $this->router->addRoute($route2);

        $route3 = new Route();
        $route3->setName('Functional Test - Security::HttpBasicAuthentication');
        $route3->setUriPattern('test/security/authentication/httpbasic(/{@action})');
        $route3->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'HttpBasicTest',
            '@action' => 'authenticate',
            '@format' => 'html'
        ]);
        $route3->setAppendExceedingArguments(true);
        $this->router->addRoute($route3);

        $route4 = new Route();
        $route4->setName('Functional Test - Security::UsernamePasswordAuthentication');
        $route4->setUriPattern('test/security/authentication/usernamepassword(/{@action})');
        $route4->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'UsernamePasswordTest',
            '@action' => 'authenticate',
            '@format' => 'html'
        ]);
        $route4->setAppendExceedingArguments(true);
        $this->router->addRoute($route4);
    }

    /**
     * On trying to access a restricted resource Flow should first store the
     * current request in the session and then redirect to the entry point. After
     * successful authentication the intercepted request should be contained in
     * the security context and can be fetched from there.
     *
     * @test
     */
    public function theInterceptedRequestIsStoredInASessionForLaterRetrieval()
    {
        $this->markTestIncomplete();

        // At this time, we can't really test this case because the security context
        // does not contain any authentication tokens or a properly configured entry
        // point. Also the browser lacks support for cookies which would enable us
        // to simulate a full round trip.

        // -> should be a redirect to some login page
        // -> then: send login form
        // -> then: expect a redirect to the above page and $this->securityContext->getInterceptedRequest() should contain the expected request
    }

    /**
     * @test
     */
    public function successfulAuthenticationResetsAuthenticatedRoles()
    {
        $uri = new Uri('http://localhost/test/security/authentication/httpbasic');
        $request = Request::create($uri);
        $request->setHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
        $response = $this->browser->sendRequest($request);
        $this->assertSame($response->getContent(), 'HttpBasicTestController success!' . chr(10) . 'Neos.Flow:Everybody' . chr(10) . 'Neos.Flow:AuthenticatedUser' . chr(10) . 'Neos.Flow:Administrator' . chr(10));
    }

    /**
     * @test
     */
    public function successfulAuthenticationCallsOnAuthenticationSuccessMethod()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'functional_test_account';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'a_very_secure_long_password';

        $response = $this->browser->request('http://localhost/test/security/authentication/usernamepassword', 'POST', $arguments);
        $this->assertSame($response->getContent(), 'UsernamePasswordTestController success!' . chr(10) . 'Neos.Flow:Everybody' . chr(10) . 'Neos.Flow:AuthenticatedUser' . chr(10) . 'Neos.Flow:Administrator' . chr(10));
    }

    /**
     * @test
     */
    public function failedAuthenticationCallsOnAuthenticationFailureMethod()
    {
        $response = $this->browser->request('http://localhost/test/security/authentication');
        $this->assertContains('Uncaught Exception in Flow #42: Failure Method Exception', $response->getContent());
    }

    /**
     * @test
     */
    public function successfulAuthenticationDoesNotStartASessionIfNoTokenRequiresIt()
    {
        $uri = new Uri('http://localhost/test/security/authentication/httpbasic');
        $request = Request::create($uri);
        $request->setHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
        $response = $this->browser->sendRequest($request);
        $this->assertEmpty($response->getCookies());
    }

    /**
     * @test
     */
    public function successfulAuthenticationDoesStartASessionIfTokenRequiresIt()
    {
        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'functional_test_account';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'a_very_secure_long_password';

        $response = $this->browser->request('http://localhost/test/security/authentication/usernamepassword', 'POST', $arguments);
        $this->assertNotEmpty($response->getCookies());
    }

    /**
     * @test
     */
    public function noSessionIsStartedIfAUnrestrictedActionIsCalled()
    {
        $response = $this->browser->request('http://localhost/test/security/restricted/public');
        $this->assertEmpty($response->getCookies());
    }
}
