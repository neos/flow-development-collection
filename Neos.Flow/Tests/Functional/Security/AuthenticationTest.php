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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

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
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * @return void
     */
    protected function setUp(): void
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

        $this->registerRoute(
            'Functional Test - Security::Restricted',
            'test/security/restricted(/{@action})',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Security\Fixtures',
                '@controller' => 'Restricted',
                '@action' => 'public',
                '@format' =>'html'
            ],
            true
        );

        $this->registerRoute(
            'Functional Test - Security::Authentication',
            'test/security/authentication(/{@action})',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Security\Fixtures',
                '@controller' => 'Authentication',
                '@action' => 'authenticate',
                '@format' => 'html'
            ],
            true
        );

        $this->registerRoute(
            'Functional Test - Security::HttpBasicAuthentication',
            'test/security/authentication/httpbasic(/{@action})',
            [
                 '@package' => 'Neos.Flow',
                 '@subpackage' => 'Tests\Functional\Security\Fixtures',
                 '@controller' => 'HttpBasicTest',
                 '@action' => 'authenticate',
                 '@format' => 'html'
             ],
            true
        );

        $this->registerRoute(
            'Functional Test - Security::UsernamePasswordAuthentication',
            'test/security/authentication/usernamepassword(/{@action})',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Security\Fixtures',
                '@controller' => 'UsernamePasswordTest',
                '@action' => 'authenticate',
                '@format' => 'html'
            ],
            true
        );

        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
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

        $request = $this->serverRequestFactory->createServerRequest('GET', $uri);
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
        $response = $this->browser->sendRequest($request);
        self::assertSame(
            'HttpBasicTestController success!' . chr(10) . 'Neos.Flow:Everybody' . chr(10) . 'Neos.Flow:AuthenticatedUser' . chr(10) . 'Neos.Flow:Administrator' . chr(10),
            $response->getBody()->getContents()
        );
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
        self::assertSame(
            'UsernamePasswordTestController success!' . chr(10) . 'Neos.Flow:Everybody' . chr(10) . 'Neos.Flow:AuthenticatedUser' . chr(10) . 'Neos.Flow:Administrator' . chr(10),
            $response->getBody()->getContents()
        );
    }

    /**
     * @test
     */
    public function failedAuthenticationCallsOnAuthenticationFailureMethod()
    {
        $response = $this->browser->request('http://localhost/test/security/authentication');
        self::assertStringContainsString('Uncaught Exception in Flow #42: Failure Method Exception', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function successfulAuthenticationDoesNotStartASessionIfNoTokenRequiresIt()
    {
        $uri = new Uri('http://localhost/test/security/authentication/httpbasic');
        $request = $this->serverRequestFactory->createServerRequest('GET', $uri);
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
        $response = $this->browser->sendRequest($request);
        self::assertEmpty($response->getHeader('Set-Cookie'));
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
        self::assertNotEmpty($response->getHeaderLine('Set-Cookie'));
    }

    /**
     * @test
     */
    public function noSessionIsStartedIfAUnrestrictedActionIsCalled()
    {
        $response = $this->browser->request('http://localhost/test/security/restricted/public');
        self::assertEmpty($response->getHeader('Set-Cookie'));
    }
}
