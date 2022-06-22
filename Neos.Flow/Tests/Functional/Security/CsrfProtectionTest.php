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

use Neos\Flow\Http\Cookie;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * Functional testcase for certain aspects of CSRF protection.
 *
 * Note that some other parts of this mechanism are tested in a unit testcase.
 */
class CsrfProtectionTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var Fixtures\Controller\RestrictedController
     */
    protected $restrictedController;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $accountRepository = $this->objectManager->get(AccountRepository::class);
        $accountFactory = $this->objectManager->get(AccountFactory::class);

        $account = $accountFactory->createAccountWithPassword('admin', 'password', ['Neos.Flow:Administrator'], 'UsernamePasswordTestingProvider');
        $accountRepository->add($account);
        $this->persistenceManager->persistAll();

        $this->registerRoute('authentication', 'test/security/authentication/usernamepassword(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'UsernamePasswordTest',
            '@action' => 'authenticate',
            '@format' => 'html'
        ]);

        $this->registerRoute(
            'controller',
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
    }

    /**
     * @test
     */
    public function postRequestOnRestrictedActionWithoutCsrfTokenCausesAccessDeniedException()
    {
        $this->markTestIncomplete('Needs to be implemented');

        /** @var ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);

        $arguments = [];
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'admin';
        $arguments['__authentication']['Neos']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'password';

        $request = $serverRequestFactory->createServerRequest('GET', new Uri('http://localhost/test/security/authentication/usernamepassword/authenticate'));
        $request = $request->withQueryParams($arguments);
        $response = $this->browser->sendRequest($request);

        $cookieHeader = $response->getHeaderLine('Set-Cookie');
        $cookie = Cookie::createFromRawSetCookieHeader($cookieHeader);

        $request = $serverRequestFactory->createServerRequest('GET', new Uri('http://localhost/test/security/restricted/admin'));
        $request = $request->withHeader('Cookie', (string)$cookie);
        $response = $this->browser->sendRequest($request);

        // Expect an exception because no account is authenticated:
        $response = $this->browser->request(new Uri('http://localhost/test/security/restricted/customer'), 'POST');
        // ...

        // Expect an different exception because although an account is authenticated, the request lacks a CSRF token:
        $response = $this->browser->request(new Uri('http://localhost/test/security/restricted/customer'), 'POST', $arguments);
        // ...

        // Expect that it works after you logged in
        $csrfToken = $this->securityContext->getCsrfProtectionToken();
        $request = $serverRequestFactory->createServerRequest('POST', 'http://localhost/test/security/restricted/customer');
        // ...
    }
}
