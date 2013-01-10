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

use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;

/**
 * Functional testcase for certain aspects of CSRF protection.
 *
 * Note that some other parts of this mechanism are tested in a unit testcase.
 */
class CsrfProtectionTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController
	 */
	protected $restrictedController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->restrictedController = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController');

		$this->registerRoute('test', 'test/security/restricted(/{@action})', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Security\Fixtures',
			'@controller' => 'Restricted',
			'@action' => 'public',
			'@format' =>'html'
		));
	}

	/**
	 * @test
	 */
	public function linkToPublicActionIsCsrfProtected() {
		$httpRequest = Request::create(new Uri('http://localhost/test/security/restricted/public'));
		$actionRequest = $httpRequest->createActionRequest();

		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($actionRequest);

		$uri = $uriBuilder->uriFor('public', array(), 'Restricted', 'TYPO3.Flow', 'Tests\Functional\Security\Fixtures');
		$this->assertEquals('test/security/restricted', (string)$uri);

		$this->authenticateRoles(array('Administrator'));
		$uri = $uriBuilder->uriFor('public', array(), 'Restricted', 'TYPO3.Flow', 'Tests\Functional\Security\Fixtures');
		$this->assertEquals('test/security/restricted?__csrfToken', substr($uri, 0, 36));

		$uriBuilder->setLinkProtectionEnabled(FALSE);
		$uri = $uriBuilder->uriFor('public', array(), 'Restricted', 'TYPO3.Flow', 'Tests\Functional\Security\Fixtures');
		$this->assertEquals('test/security/restricted', (string)$uri);

		$uriBuilder->reset();
		$this->assertTrue($uriBuilder->isLinkProtectionEnabled());
		$uri = $uriBuilder->uriFor('public', array(), 'Restricted', 'TYPO3.Flow', 'Tests\Functional\Security\Fixtures');
		$this->assertEquals('test/security/restricted?__csrfToken', substr($uri, 0, 36));
	}

}
?>