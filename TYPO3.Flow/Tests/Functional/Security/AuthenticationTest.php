<?php
namespace TYPO3\FLOW3\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException;
use TYPO3\FLOW3\Mvc\Routing\Route;

/**
 * Testcase for Authentication
 */
class AuthenticationTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Security\Fixtures\RestrictedController
	 */
	protected $restrictedController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$route = new Route();
		$route->setName('Functional Test - Security::Authentication');
		$route->setUriPattern('test/security/restricted(/{@action})');
		$route->setDefaults(array(
			'@package' => 'TYPO3.FLOW3',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'Restricted',
			'@action' => 'public',
			'@format' =>'html'
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);
	}

	/**
	 * On trying to access a restricted resource FLOW3 should first store the
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

		$response = $this->browser->request('http://localhost/test/security/restricted/customer');

		// -> should be a redirect to some login page
		// -> then: send login form
		// -> then: expect a redirect to the above page and $this->securityContext->getInterceptedRequest() should contain the expected request
	}

}
?>