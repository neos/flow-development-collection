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
 * Testcase for the policy expression parser
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyExpressionParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfAResourceReferencesAnUndefinedResource() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3\Foo\BasicClass->setSomeProperty()) || notExistingResource',
		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$parser =new \F3\FLOW3\Security\ACL\PolicyExpressionParser();
		$parser->injectObjectFactory($mockObjectFactory);
		$parser->injectObjectManager($mockObjectManager);
		$parser->setResourcesTree($resourcesTree);

		$parser->parse('theOneAndOnlyResource');
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\CircularResourceDefinitionDetectedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseThrowsAnExceptionIfTheResourceTreeContainsCircularReferences() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3\TestPackage\BasicClass->setSomeProperty()) || theIntegrativeResource',
			'theOtherLonelyResource' => 'method(F3\TestPackage\BasicClassValidator->.*())',
			'theIntegrativeResource' => 'theOneAndOnlyResource || theLonelyResource',

		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$parser =new \F3\FLOW3\Security\ACL\PolicyExpressionParser();
		$parser->injectObjectFactory($mockObjectFactory);
		$parser->injectObjectManager($mockObjectManager);
		$parser->setResourcesTree($resourcesTree);

		$parser->parse('theIntegrativeResource');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parseStoresTheCorrectResourceTreeTraceInTheTraceParameter() {
		$resourcesTree = array(
			'theOneAndOnlyResource' => 'method(F3\TestPackage\BasicClass->setSomeProperty())',
			'theOtherLonelyResource' => 'theOneAndOnlyResource',
			'theIntegrativeResource' => 'theOtherLonelyResource',

		);

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\Pointcut\PointcutFilterComposite', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface', array(), array(), '', FALSE);
		$mockObjectFactory->expects($this->any())->method('create')->will($this->returnValue($mockPointcutFilterComposite));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);

		$parser =new \F3\FLOW3\Security\ACL\PolicyExpressionParser();
		$parser->injectObjectFactory($mockObjectFactory);
		$parser->injectObjectManager($mockObjectManager);
		$parser->setResourcesTree($resourcesTree);

		$trace = array();
		$parser->parse('theIntegrativeResource', $trace);

		$expectedTrace = array('theIntegrativeResource', 'theOtherLonelyResource', 'theOneAndOnlyResource');

		$this->assertEquals($expectedTrace, $trace, 'The trace has not been set as expected.');
	}
}


?>