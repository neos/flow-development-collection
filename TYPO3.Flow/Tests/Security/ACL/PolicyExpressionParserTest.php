<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyExpressionParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfAResourceReferencesAnUndefinedResource() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3\Foo\BasicClass->setSomeProperty()) || notExistingResource',
		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);

		$parser =new \F3\FLOW3\Security\ACL\PolicyExpressionParser();
		$parser->injectObjectFactory($mockObjectFactory);
		$parser->injectObjectManager($mockObjectManager);
		$parser->setResourcesTree($resourcesTree);

		$parser->parse('theOneAndOnlyResource');
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\CircularResourceDefinitionDetected
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfTheResourceTreeContainsCircularReferences() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3\TestPackage\BasicClass->setSomeProperty()) || theIntegrativeResource',
			'theOtherLonelyResource' => 'method(F3\TestPackage\BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theLonelyResource',

		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);

		$parser =new \F3\FLOW3\Security\ACL\PolicyExpressionParser();
		$parser->injectObjectFactory($mockObjectFactory);
		$parser->injectObjectManager($mockObjectManager);
		$parser->setResourcesTree($resourcesTree);

		$parser->parse('theIntegrativeResource');
	}
}


?>