<?php
namespace TYPO3\Flow\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Authentication\Provider\TestingProvider;
use TYPO3\Flow\Security\Authentication\TokenInterface;

/**
 * Testcase for Authentication
 */
class AuthenticationTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$accountRepository = $this->objectManager->get('\TYPO3\Flow\Security\AccountRepository');
		$accountFactory = $this->objectManager->get('\TYPO3\Flow\Security\AccountFactory');

		$account = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', array('TYPO3.Flow:Administrator'), 'TestingProvider');
		$accountRepository->add($account);
		$account2 = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', array('TYPO3.Flow:Administrator'), 'HttpBasicTestingProvider');
		$accountRepository->add($account2);
		$account3 = $accountFactory->createAccountWithPassword('functional_test_account', 'a_very_secure_long_password', array('TYPO3.Flow:Administrator'), 'UsernamePasswordTestingProvider');
		$accountRepository->add($account3);
		$this->persistenceManager->persistAll();

		$route = new Route();
		$route->setName('Functional Test - Security::Restricted');
		$route->setUriPattern('test/security/restricted(/{@action})');
		$route->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'Restricted',
			'@action' => 'public',
			'@format' =>'html'
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);

		$route2 = new Route();
		$route2->setName('Functional Test - Security::Authentication');
		$route2->setUriPattern('test/security/authentication(/{@action})');
		$route2->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'Authentication',
			'@action' => 'authenticate',
			'@format' => 'html'
		));
		$route2->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route2);

		$route3 = new Route();
		$route3->setName('Functional Test - Security::HttpBasicAuthentication');
		$route3->setUriPattern('test/security/authentication/httpbasic(/{@action})');
		$route3->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'HttpBasicTest',
			'@action' => 'authenticate',
			'@format' => 'html'
		));
		$route3->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route3);

		$route4 = new Route();
		$route4->setName('Functional Test - Security::UsernamePasswordAuthentication');
		$route4->setUriPattern('test/security/authentication/usernamepassword(/{@action})');
		$route4->setDefaults(array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'UsernamePasswordTest',
			'@action' => 'authenticate',
			'@format' => 'html'
		));
		$route4->setAppendExceedingArguments(TRUE);
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
	public function theInterceptedRequestIsStoredInASessionForLaterRetrieval() {
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
	public function successfulAuthenticationResetsAuthenticatedRoles() {
		$uri = new Uri('http://localhost/test/security/authentication/httpbasic');
		$request = Request::create($uri);
		$request->setHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
		$response = $this->browser->sendRequest($request);
		$this->assertSame($response->getContent(), 'HttpBasicTestController success!' . chr(10) . 'Everybody' . chr(10) . 'AuthenticatedUser' . chr(10) . 'TYPO3.Flow:Administrator' . chr(10));
	}

	/**
	 * @test
	 */
	public function successfulAuthenticationCallsOnAuthenticationSuccessMethod() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'functional_test_account';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'a_very_secure_long_password';

		$response = $this->browser->request('http://localhost/test/security/authentication/usernamepassword', 'POST', $arguments);
		$this->assertSame($response->getContent(), 'UsernamePasswordTestController success!' . chr(10) . 'Everybody' . chr(10) . 'AuthenticatedUser' . chr(10) . 'TYPO3.Flow:Administrator' . chr(10));
	}

	/**
	 * @test
	 */
	public function failedAuthenticationCallsOnAuthenticationFailureMethod() {
		$response = $this->browser->request('http://localhost/test/security/authentication');
		$this->assertContains('Uncaught Exception in Flow #42: Failure Method Exception', $response->getContent());
	}

	/**
	 * @test
	 */
	public function successfulAuthenticationDoesNotStartASessionIfNoTokenRequiresIt() {
		$uri = new Uri('http://localhost/test/security/authentication/httpbasic');
		$request = Request::create($uri);
		$request->setHeader('Authorization', 'Basic ' . base64_encode('functional_test_account:a_very_secure_long_password'));
		$response = $this->browser->sendRequest($request);
		$this->assertEmpty($response->getCookies());
	}

	/**
	 * @test
	 */
	public function successfulAuthenticationDoesStartASessionIfTokenRequiresIt() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'functional_test_account';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'a_very_secure_long_password';

		$response = $this->browser->request('http://localhost/test/security/authentication/usernamepassword', 'POST', $arguments);
		$this->assertNotEmpty($response->getCookies());
	}

	/**
	 * @test
	 */
	public function noSessionIsStartedIfAUnrestrictedActionIsCalled() {
		$response = $this->browser->request('http://localhost/test/security/restricted/public');
		$this->assertEmpty($response->getCookies());
	}
}

?>