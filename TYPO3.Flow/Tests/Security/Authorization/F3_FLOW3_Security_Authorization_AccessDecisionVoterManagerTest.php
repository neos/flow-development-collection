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
class AccessDecisionVoterManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theManagerCallsEveryConfiguredVoterToVoteIfItSupportsTheCurrentClassAndMethodName() {
		$this->markTestIncomplete();

		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);

		$mockJoinPoint = $this->getMock('F3_FLOW3_AOP_JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->atLeastOnce())->method('getClassName')->will($this->returnValue('someClassName'));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodName')->will($this->returnValue('someMethodName'));

		$voter1 = $this->getMock('F3_FLOW3_Security_Authorization_VoterInterface', array(), array(), 'voter1');
		$voter2 = $this->getMock('F3_FLOW3_Security_Authorization_VoterInterface', array(), array(), 'voter2');
		$voter3 = $this->getMock('F3_FLOW3_Security_Authorization_VoterInterface', array(), array(), 'voter3');

		$voter1->expects($this->once())->method('vote')->with($this->equalTo($mockJoinPoint));
		$voter2->expects($this->once())->method('vote')->with($this->equalTo($mockJoinPoint));
		$voter3->expects($this->once())->method('vote')->with($this->equalTo($mockJoinPoint));

		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->voters = array('voter1', 'voter2', 'voter3');
		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$voterManager = new F3::FLOW3::Security::Authorization::AccessDecisionVoterManager();
		$voterManager->decide($mockContext, $mockJoinPoint);
	}
}
?>