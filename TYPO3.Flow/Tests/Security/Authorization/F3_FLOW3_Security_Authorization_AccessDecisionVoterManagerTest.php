<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

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
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the access decision voter manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AccessDecisionVoterManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideThrowsAnExceptionIfOneVoterReturnsADenyVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['accessDecisionVoters'] = array('F3\TestPackage\AccessGrantVoter', 'F3\TestPackage\AccessDenyVoter', 'F3\TestPackage\AccessGrantVoter');
		$settings['security']['allowAccessIfAllVotersAbstain'] = FALSE;
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$voterManager = new \F3\FLOW3\Security\Authorization\AccessDecisionVoterManager($mockConfigurationManager, $this->objectManager);

		try {
			$voterManager->decide($mockContext, $mockJoinPoint);
			$this->fail('No exception has been thrown');
		} catch (\F3\FLOW3\Security\Exception\AccessDenied $exception) {}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideThrowsAnExceptionIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['accessDecisionVoters'] = array('F3\TestPackage\AbstainingVoter', 'F3\TestPackage\AbstainingVoter', 'F3\TestPackage\AbstainingVoter');
		$settings['security']['allowAccessIfAllVotersAbstain'] = FALSE;
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$voterManager = new \F3\FLOW3\Security\Authorization\AccessDecisionVoterManager($mockConfigurationManager, $this->objectManager);

		try {
			$voterManager->decide($mockContext, $mockJoinPoint);
			$this->fail('No exception has been thrown');
		} catch (\F3\FLOW3\Security\Exception\AccessDenied $exception) {}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideGrantsAccessIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['accessDecisionVoters'] = array('F3\TestPackage\AbstainingVoter', 'F3\TestPackage\AbstainingVoter', 'F3\TestPackage\AbstainingVoter');
		$settings['security']['allowAccessIfAllVotersAbstain'] = TRUE;
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$voterManager = new \F3\FLOW3\Security\Authorization\AccessDecisionVoterManager($mockConfigurationManager, $this->objectManager);

		$voterManager->decide($mockContext, $mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function decideGrantsAccessIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['accessDecisionVoters'] = array('F3\TestPackage\AccessGrantVoter', 'F3\TestPackage\AbstainingVoter', 'F3\TestPackage\AbstainingVoter');
		$settings['security']['allowAccessIfAllVotersAbstain'] = TRUE;
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$voterManager = new \F3\FLOW3\Security\Authorization\AccessDecisionVoterManager($mockConfigurationManager, $this->objectManager);

		$voterManager->decide($mockContext, $mockJoinPoint);
	}
}
?>