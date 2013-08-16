<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the access decision voter manager
 *
 */
class AccessDecisionVoterManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function decideOnJoinPointThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function decideOnJoinPointThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function decideOnJoinPointGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);

		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function decideOnJoinPointGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnJoinPoint($mockJoinPoint);

		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function decideOnResourceThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function decideOnResourceThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', FALSE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');
	}

	/**
	 * @test
	 */
	public function decideOnResourceGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('allowAccessIfAllAbstain', TRUE);
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');

		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function decideOnResourceGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
		$voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
		$voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', FALSE);
		$voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

		$voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', FALSE);
		$voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
		$voterManager->_set('securityContext', $mockContext);

		$voterManager->decideOnResource('myResource');

		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}
}
?>