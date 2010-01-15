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
	 * @expectedException F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnJoinPointGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideOnResourceGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\Authorization\AccessDecisionVoterManager'), array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}
}
?>