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

use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Security\Authorization\PrivilegeVoteResult;
use TYPO3\Flow\Security\Authorization\PrivilegeManager;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the privilege manager
 *
 */
class PrivilegeManagerTest extends UnitTestCase {

	/**
	 * @var Context|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityContext;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var JoinPointInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockJoinPoint;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPrivilege1;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPrivilege2;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPrivilege3;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $grantPrivilege;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $denyPrivilege;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $abstainPrivilege;

	/**
	 * @var PrivilegeManager
	 */
	protected $privilegeManager;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();
		$this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();

		$this->mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->getMock();

		$mockPrivilege1ClassName = 'mockPrivilege1';
		$this->mockPrivilege1 = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface')->setMockClassName($mockPrivilege1ClassName)->getMock();
		$mockPrivilege2ClassName = 'mockPrivilege2';
		$this->mockPrivilege2 = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface')->setMockClassName($mockPrivilege2ClassName)->getMock();
		$mockPrivilege3ClassName = 'mockPrivilege3';
		$this->mockPrivilege3 = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface')->setMockClassName($mockPrivilege3ClassName)->getMock();

		$this->mockReflectionService = $this->getMockBuilder('TYPO3\Flow\Reflection\ReflectionService')->getMock();
		$this->mockReflectionService->expects($this->any())->method('getAllImplementationClassNamesForInterface')->will($this->returnValue(array($mockPrivilege1ClassName, $mockPrivilege2ClassName, $mockPrivilege3ClassName)));
		$this->mockReflectionService->expects($this->any())->method('isClassAbstract')->will($this->returnValue(FALSE));

		$this->privilegeManager = new PrivilegeManager($this->mockObjectManager, $this->mockReflectionService, $this->mockSecurityContext);

		$this->grantPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->setMethods(array('vote'))->getMock();
		$this->inject($this->grantPrivilege, 'permission', PrivilegeInterface::GRANT);

		$this->denyPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->setMethods(array('vote'))->getMock();
		$this->inject($this->denyPrivilege, 'permission', PrivilegeInterface::DENY);

		$this->abstainPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->setMethods(array('vote'))->getMock();
		$this->inject($this->abstainPrivilege, 'permission', PrivilegeInterface::ABSTAIN);
	}

	/**
	 * @test
	 */
	public function isGrantedReturnsFalseIfOneVoterReturnsADenyVote() {
		$this->mockPrivilege1->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_GRANT)));
		$this->mockPrivilege2->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege3->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_DENY)));

		$this->assertFalse($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedReturnsFalseIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$this->mockPrivilege1->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege2->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege3->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));

		$this->assertFalse($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedReturnsTrueIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$this->mockPrivilege1->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege2->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege3->staticExpects($this->any())->method('vote')->with($this->mockJoinPoint)->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));

		$this->inject($this->privilegeManager, 'allowAccessIfAllAbstain', TRUE);

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedReturnsTrueIfThereIsNoDenyVoteAndOneGrantVote() {
		$this->mockPrivilege1->staticExpects($this->any())->method('vote')->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));
		$this->mockPrivilege2->staticExpects($this->any())->method('vote')->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_GRANT)));
		$this->mockPrivilege3->staticExpects($this->any())->method('vote')->will($this->returnValue(new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_ABSTAIN)));

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsFalseIfOneVoterReturnsADenyVote() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->grantPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->denyPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsFalseIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedPrivilegeReturnsTrueIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$this->inject($this->privilegeManager, 'allowAccessIfAllAbstain', TRUE);

		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsTrueIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->grantPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}
}
