<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization::Voter;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * An access decision voter, that asks the FLOW3 ACLService for a decision.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ACL implements F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface {

	/**
	 * The policy service
	 * @var F3::FLOW3::Security::ACL::PolicyService
	 */
	protected $policyService;

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Security::ACL::PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Security::ACL::PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * This is the default ACL voter, it votes for the ACCESS privilege
	 *
	 * @param F3::FLOW3::Security::Context $securityContext The current securit context
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws F3::FLOW3::Security::Exception::AccessDenied If access is not granted
	 */
	public function vote(F3::FLOW3::Security::Context $securityContext, F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($securityContext->getAuthenticationTokens() as $token) {
			foreach ($token->getGrantedAuthorities() as $grantedAuthority) {
				$privileges = $this->policyService->getPrivileges($grantedAuthority, $joinPoint, 'ACCESS');
				if (!isset($privileges[0])) continue;

				if ($privileges[0]->isGrant()) $accessGrants++;
				else $accessDenies++;
			}
		}

		if ($accessDenies > 0) return self::VOTE_DENY;
		if ($accessGrants > 0) return self::VOTE_GRANT;

		return VOTE_ABSTAIN;
	}
}

?>