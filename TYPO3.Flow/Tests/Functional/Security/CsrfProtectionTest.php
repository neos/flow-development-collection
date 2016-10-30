<?php
namespace TYPO3\Flow\Tests\Functional\Security;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\AccountFactory;
use TYPO3\Flow\Security\AccountRepository;
use TYPO3\Flow\Tests\FunctionalTestCase;

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
    public function setUp()
    {
        parent::setUp();

        $accountRepository = $this->objectManager->get(AccountRepository::class);
        $accountFactory = $this->objectManager->get(AccountFactory::class);

        $account = $accountFactory->createAccountWithPassword('admin', 'password', ['TYPO3.Flow:Administrator'], 'UsernamePasswordTestingProvider');
        $accountRepository->add($account);
        $this->persistenceManager->persistAll();

        $this->registerRoute('authentication', 'test/security/authentication/usernamepassword(/{@action})', [
            '@package' => 'TYPO3.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'UsernamePasswordTest',
            '@action' => 'authenticate',
            '@format' => 'html'
        ]);

        $this->registerRoute('controller', 'test/security/restricted(/{@action})', [
            '@package' => 'TYPO3.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'Restricted',
            '@action' => 'public',
            '@format' =>'html'
            ], true
        );
    }

    /**
     * @test
     */
    public function postRequestOnRestrictedActionWithoutCsrfTokenCausesAccessDeniedException()
    {
        $this->markTestIncomplete('Needs to be implemented');

        $arguments = [];
        $arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'admin';
        $arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'password';

        $request = Request::create(new Uri('http://localhost/test/security/authentication/usernamepassword/authenticate'), 'POST', $arguments);
        $response = $this->browser->sendRequest($request);

        $sessionCookie = $response->getCookie('TYPO3_Flow_Session');

        $request = Request::create(new Uri('http://localhost/test/security/restricted/admin'));
        $request->setCookie($sessionCookie);
        $response = $this->browser->sendRequest($request);

        // Expect an exception because no account is authenticated:
        $response = $this->browser->request(new Uri('http://localhost/test/security/restricted/customer'), 'POST');
        // ...

        // Expect an different exception because although an account is authenticated, the request lacks a CSRF token:
        $response = $this->browser->request(new Uri('http://localhost/test/security/restricted/customer'), 'POST', $arguments);
        // ...

        // Expect that it works after you logged in
        $csrfToken = $this->securityContext->getCsrfProtectionToken();
        $request = Request::create(new Uri('http://localhost/test/security/restricted/customer'), 'POST');
        // ...
    }
}
