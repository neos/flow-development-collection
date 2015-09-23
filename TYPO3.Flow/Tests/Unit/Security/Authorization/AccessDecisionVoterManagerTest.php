<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the access decision voter manager
 *
 */
class AccessDecisionVoterManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
     */
    public function decideOnJoinPointThrowsAnExceptionIfOneVoterReturnsADenyVote()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('securityContext', $mockContext);

        $voterManager->decideOnJoinPoint($mockJoinPoint);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
     */
    public function decideOnJoinPointThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('allowAccessIfAllAbstain', false);
        $voterManager->_set('securityContext', $mockContext);

        $voterManager->decideOnJoinPoint($mockJoinPoint);
    }

    /**
     * @test
     */
    public function decideOnJoinPointGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('allowAccessIfAllAbstain', true);
        $voterManager->_set('securityContext', $mockContext);

        $voterManager->decideOnJoinPoint($mockJoinPoint);

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function decideOnJoinPointGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForJoinPoint')->with($mockContext, $mockJoinPoint)->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('securityContext', $mockContext);

        $voterManager->decideOnJoinPoint($mockJoinPoint);

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function hasAccessToResourceReturnsFalseIfOneVoterReturnsADenyVote()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('securityContext', $mockContext);

        $this->assertFalse($voterManager->hasAccessToResource('myResource'));
    }

    /**
     * @test
     */
    public function hasAccessToResourceThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('allowAccessIfAllAbstain', false);
        $voterManager->_set('securityContext', $mockContext);

        $this->assertFalse($voterManager->hasAccessToResource('myResource'));
    }

    /**
     * @test
     */
    public function hasAccessToResourceGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('allowAccessIfAllAbstain', true);
        $voterManager->_set('securityContext', $mockContext);

        $this->assertTrue($voterManager->hasAccessToResource('myResource'));
    }

    /**
     * @test
     */
    public function hasAccessToResourceGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote()
    {
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);

        $voter1 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter1->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));
        $voter2 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter2->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT));
        $voter3 = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', array(), array(), '', false);
        $voter3->expects($this->any())->method('voteForResource')->with($mockContext, 'myResource')->will($this->returnValue(\TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN));

        $voterManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('dummy'), array(), '', false);
        $voterManager->_set('accessDecisionVoters', array($voter1, $voter2, $voter3));
        $voterManager->_set('securityContext', $mockContext);

        $this->assertTrue($voterManager->hasAccessToResource('myResource'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\AccessDeniedException
     */
    public function decideOnResourceThrowsAccessDeniedExceptionIfAccessIsDenied()
    {
        $voterManager = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager', array('hasAccessToResource'), array(), '', false);
        $voterManager->expects($this->atLeastOnce())->method('hasAccessToResource')->with('Foo')->will(($this->returnValue(false)));
        $voterManager->decideOnResource('Foo');
    }
}
