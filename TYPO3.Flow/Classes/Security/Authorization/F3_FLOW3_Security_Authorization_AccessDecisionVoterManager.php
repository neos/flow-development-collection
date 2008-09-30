<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization;

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
 *
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AccessDecisionVoterManager implements F3::FLOW3::Security::Authorization::AccessDecisionManagerInterface {

	/**
	 * The component factory
	 * @var F3::FLOW3::Component::FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * The component manager
	 * @var F3::FLOW3::Component::ManagerInterface
	 */
	protected $componentManager;

	/**
	 * Array of F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface objects
	 * @var array
	 */
	protected $accessDecisionVoters = array();

	/**
	 * If set to TRUE access will be granted for objects where all voters abstain from decision.
	 * @var boolean
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * Constructor.
	 *
	 * @param F3::FLOW3::Configuration::Manager $configurationManager The configuration manager
	 * @param F3::FLOW3::Component::ManagerInterface $componentManager The component manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Configuration::Manager $configurationManager, F3::FLOW3::Component::ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $this->componentManager->getComponentFactory();

		$configuration = $configurationManager->getSettings('FLOW3');
		$this->createAccessDecisionVoters($configuration->security->accessDecisionVoters);
		$this->allowAccessIfAllAbstain = $configuration->security->allowAccessIfAllVotersAbstain;
	}

	/**
	 * Returns the configured access decision voters
	 *
	 * @return array Array of F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAccessDecisionVoters() {
		return $this->accessDecisionVoters();
	}

	/**
	 * Decides if access should be granted on the given object in the current security context.
	 * It iterates over all available F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param F3::FLOW3::Security::Context $securityContext The current securit context
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return void
	 * @throws F3::FLOW3::Security::Exception::AccessDenied If access is not granted
	 */
	public function decide(F3::FLOW3::Security::Context $securityContext, F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		$denyVotes = 0;
		$grantVotes = 0;
		$abstainVotes = 0;

		foreach ($this->accessDecisionVoters as $voter) {
			$vote = $voter->vote($securityContext, $joinPoint);
			switch ($vote) {
				case F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface::VOTE_DENY:
					$denyVotes++;
					break;
				case F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface::VOTE_GRANT:
					$grantVotes++;
					break;
				case F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface::VOTE_ABSTAIN:
					$abstainVotes++;
					break;
			}
		}

		if ($denyVotes === 0 && $grantVotes > 0) return;
		if ($denyVotes === 0 && $grantVotes === 0 && $abstainVotes > 0 && $this->allowAccessIfAllAbstain === TRUE) return;

		throw new F3::FLOW3::Security::Exception::AccessDenied('Access denied.', 1222268609);
	}

	/**
	 * Creates and sets the configured access decision voters
	 *
	 * @param array Array of access decision voter classes
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createAccessDecisionVoters($voterClasses) {
		foreach ($voterClasses as $voterClass) {
			if (!$this->componentManager->isComponentRegistered($voterClass)) throw new F3::FLOW3::Security::Exception::VoterNotFound('No voter of type ' . $voterClass . ' found!', 1222267934);

			$voter = $this->componentFactory->getComponent($voterClass);
			if (!($voter instanceof F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface)) throw new F3::FLOW3::Security::Exception::VoterNotFound('The found voter class did not implement F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface', 1222268008);

			$this->accessDecisionVoters[] = $voter;
		}
	}
}

?>