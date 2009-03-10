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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PointcutExpressionParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\FactoryInteface
	 */
	protected $mockObjectFactory;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setup() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array(), array(), '', FALSE);
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseThrowsExceptionIfPointcutExpressionIsNotAString() {
		$parser = new \F3\FLOW3\AOP\PointcutExpressionParser();
		$parser->parse(FALSE);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseThrowsExceptionIfThePointcutExpressionContainsNoDesignator() {
		$parser = new \F3\FLOW3\AOP\PointcutExpressionParser();
		$parser->injectObjectFactory($this->mockObjectFactory);
		$parser->parse('()');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseCallsSpecializedMethodsToParseEachDesignator() {
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassTaggedWith', 'parseDesignatorClass', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting');
		$parser = $this->getMock('F3\FLOW3\AOP\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->expects($this->once())->method('parseDesignatorPointcut')->with('&&', '\Foo\Bar->baz', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClassTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethodTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('&&', 'Foo->Bar()', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&', 'Bar', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorFilter')->with('&&', '\Foo\Bar\Baz', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorSetting')->with('&&', 'Foo.Bar.baz', $mockPointcutFilterComposite);

		$parser->parse('\Foo\Bar->baz');
		$parser->parse('classTaggedWith(foo)');
		$parser->parse('class(Foo)');
		$parser->parse('methodTaggedWith(foo)');
		$parser->parse('method(Foo->Bar())');
		$parser->parse('within(Bar)');
		$parser->parse('filter(\Foo\Bar\Baz)');
		$parser->parse('setting(Foo.Bar.baz)');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseSplitsUpTheExpressionIntoDesignatorsAndPassesTheOperatorsToTheDesginatorParseMethod() {
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutFilterComposite')->will($this->returnValue($mockPointcutFilterComposite));

		$mockMethods = array('parseDesignatorPointcut', 'parseDesignatorClassTaggedWith', 'parseDesignatorClass', 'parseDesignatorMethodTaggedWith', 'parseDesignatorMethod', 'parseDesignatorWithin', 'parseDesignatorFilter', 'parseDesignatorSetting');
		$parser = $this->getMock('F3\FLOW3\AOP\PointcutExpressionParser', $mockMethods, array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->expects($this->once())->method('parseDesignatorClassTaggedWith')->with('&&', 'foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorClass')->with('&&', 'Foo', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorMethod')->with('||', 'Foo->Bar()', $mockPointcutFilterComposite);
		$parser->expects($this->once())->method('parseDesignatorWithin')->with('&&!', 'Bar', $mockPointcutFilterComposite);

		$parser->parse('classTaggedWith(foo) && class(Foo) || method(Foo->Bar()) && !within(Bar)');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorClassTaggedWithAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutClassTaggedWithFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutClassTaggedWithFilter', 'foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorClassTaggedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorClassAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutClassNameFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutClassNameFilter', 'Foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorClass', '&&', 'Foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodTaggedWithAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutMethodTaggedWithFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutMethodTaggedWithFilter', 'foo')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorMethodTaggedWith', '&&', 'foo', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodAddsAClassNameFilterAndAMethodNameFilterToTheGivenFilterComposite() {
		$mockClassNameFilter = $this->getMock('F3\FLOW3\AOP\PointcutClassNameFilter', array(), array(), '', FALSE);
		$mockMethodNameFilter = $this->getMock('F3\FLOW3\AOP\PointcutMethodNameFilter', array(), array(), '', FALSE);

		$mockSubComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockSubComposite->expects($this->at(0))->method('addFilter')->with('&&', $mockClassNameFilter);
		$mockSubComposite->expects($this->at(1))->method('addFilter')->with('&&', $mockMethodNameFilter);

		$mockComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockComposite->expects($this->once())->method('addFilter')->with('&&', $mockSubComposite);

		$this->mockObjectFactory->expects($this->at(0))->method('create')->with('F3\FLOW3\AOP\PointcutFilterComposite')->will($this->returnValue($mockSubComposite));
		$this->mockObjectFactory->expects($this->at(1))->method('create')->with('F3\FLOW3\AOP\PointcutClassNameFilter', 'Foo')->will($this->returnValue($mockClassNameFilter));
		$this->mockObjectFactory->expects($this->at(2))->method('create')->with('F3\FLOW3\AOP\PointcutMethodNameFilter', 'bar', 'protected')->will($this->returnValue($mockMethodNameFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorMethod', '&&', 'protected Foo->bar()', $mockComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorMethodThrowsAnExceptionIfTheExpressionLacksTheClassMethodArrow() {
		$mockComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorMethod', '&&', 'Foo bar', $mockComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorWithinAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutClassTypeFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutClassTypeFilter', 'Bar')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorWithin', '&&', 'Bar', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorPointcutAddsAFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\AOP\PointcutFilter', '\Foo\Bar', 'baz')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectFactory($this->mockObjectFactory);

		$parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar->baz', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorPointcutThrowsAnExceptionIfTheExpressionLacksTheAspectClassMethodArrow() {
		$mockComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->_call('parseDesignatorPointcut', '&&', '\Foo\Bar', $mockComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorFilterAddsACustomFilterToTheGivenFilterComposite() {
		$mockFilter = $this->getMock('F3\FLOW3\AOP\PointcutFilter', array(), array(), '', FALSE);
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);
		$mockPointcutFilterComposite->expects($this->once())->method('addFilter')->with('&&', $mockFilter);

		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'F3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parseDesignatorFilterThrowsAnExceptionIfACustomFilterDoesNotImplementThePointcutFilterInterface() {
		$mockFilter = new \ArrayObject();
		$mockPointcutFilterComposite = $this->getMock('F3\FLOW3\AOP\PointcutFilterComposite', array(), array(), '', FALSE);

		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\Foo\Custom\Filter')->will($this->returnValue($mockFilter));

		$parser = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\AOP\PointcutExpressionParser'), array('dummy'), array(), '', FALSE);
		$parser->injectObjectManager($this->mockObjectManager);

		$parser->_call('parseDesignatorFilter', '&&', 'F3\Foo\Custom\Filter', $mockPointcutFilterComposite);
	}
}
?>