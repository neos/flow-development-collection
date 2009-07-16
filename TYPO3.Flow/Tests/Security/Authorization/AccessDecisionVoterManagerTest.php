<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the access decision voter manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AccessDecisionVoterManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\AccessDenied
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));

		$voterManager->decide($mockContext, $mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\AccessDenied
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);

		$voterManager->decide($mockContext, $mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);

		$voterManager->decide($mockContext, $mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('vote')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));

		$voterManager->decide($mockContext, $mockJoinPoint);
	}
}
?>