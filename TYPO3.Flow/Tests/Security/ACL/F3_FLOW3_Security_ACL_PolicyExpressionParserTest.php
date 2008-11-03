<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::ACL;

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
 * Testcase for the policy expression parser
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PolicyExpressionParserTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfAResourceReferencesAnUndefinedResource() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty())',
			'theOtherLonelyResource' => 'method(F3::TestPackage::BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theLonelyResource',
		);

		$parser = new PolicyExpressionParser($this->componentManager);
		$parser->setResourcesTree($resourcesTree);
		try {
			$parser->parse('theIntegrativeResource');
			$this->fail('The expected F3::FLOW3::AOP::Exception::InvalidPointcutExpression exception has not been thrown.');
		} catch (F3::FLOW3::AOP::Exception::InvalidPointcutExpression $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfTheResourceTreeContainsCircularReferences() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3::TestPackage::BasicClass->setSomeProperty()) || theIntegrativeResource',
			'theOtherLonelyResource' => 'method(F3::TestPackage::BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theLonelyResource',
		);

		$parser = new PolicyExpressionParser($this->componentManager);
		$parser->setResourcesTree($resourcesTree);
		try {
			$parser->parse('theIntegrativeResource');
			$this->fail('The expected F3::FLOW3::Security::Exception::CircularResourceDefinitionDetected exception has not been thrown.');
		} catch (F3::FLOW3::Security::Exception::CircularResourceDefinitionDetected $exception) {}
	}
}

?>