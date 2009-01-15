<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Testcase for the default AOP Pointcut implementation
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesChecksIfTheGivenClassAndMethodMatchThePointcutFilterComposite() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array('matches'), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('matches')->with($className, $methodName, $className, 1)->will($this->returnValue(TRUE));

		$pointcut = $this->getMock('F3\FLOW3\AOP\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertTrue($pointcut->matches($className, $methodName, $className, 1));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\AOP\Exception\CircularPointcutReference
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesDetectsCircularMatchesAndThrowsAndException() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('F3\FLOW3\AOP\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		for ($i = -1; $i <= \F3\FLOW3\AOP\Pointcut::MAXIMUM_RECURSIONS; $i++) {
			$pointcut->matches($className, $methodName, $className,1);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutExpressionReturnsThePointcutExpression() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('F3\FLOW3\AOP\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertSame($pointcutExpression, $pointcut->getPointcutExpression());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectClassNameReturnsTheAspectClassName() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('F3\FLOW3\AOP\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName), '', TRUE);
		$this->assertSame($aspectClassName, $pointcut->getAspectClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutMethodNameReturnsThePointcutMethodName() {
		$pointcutExpression = 'ThePointcutExpression';
		$aspectClassName = 'TheAspect';
		$className = 'TheClass';
		$methodName = 'TheMethod';

		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array('matches'), array(), '', FALSE);

		$pointcut = $this->getMock('F3\FLOW3\AOP\Pointcut', array('dummy'), array($pointcutExpression, $mockPointcutFilterComposite, $aspectClassName, 'PointcutMethod'), '', TRUE);
		$this->assertSame('PointcutMethod', $pointcut->getPointcutMethodName());
	}
}
?>