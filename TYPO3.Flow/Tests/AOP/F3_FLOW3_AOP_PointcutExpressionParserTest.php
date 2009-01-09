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

require_once('F3_FLOW3_AOP_MockPointcutExpressionParser.php');
require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_CustomFilter.php');
require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_EmptyClass.php');

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutExpressionParserTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\AOP\PointcutExpressionParser
	 */
	protected $parser;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->parser = $this->objectManager->getObject('F3\FLOW3\AOP\PointcutExpressionParser');
	}

	/**
	 * Checks if the parser throws an exception if the expression is no string
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserThrowsExceptionIfExpressionIsNotString() {
		try {
			$this->parser->parse(FALSE);
		} catch (\Exception $exception) {
			$this->assertEquals(1168874738, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was no string.');
	}

	/**
	 * Checks if the parser throws an exception if the pointcut function in the second part of the expression is missing.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function missingPointcutFunctionThrowsException() {
		try {
			$this->parser->parse('method(F3\TestPackage\BasicClass->*(..)) || ())');
		} catch (\Exception $exception) {
			$this->assertEquals(1168874739, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was invalid.');
	}

	/**
	 * Checks if the parser throws an exception if no "->" is found in the signature pattern
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function missingArrowInSignaturePatternThrowsException() {
		try {
			$this->parser->parse('method(F3\TestPackage\BasicClass)');
		} catch (\Exception $exception) {
			$this->assertEquals(1169027339, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was invalid.');
	}

	/**
	 * Checks if the parser parses an expression with two simple classes connected by an || operator
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleClassWithOrOperatorIsParsedCorrectly() {
		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();

		$firstSubComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$firstSubComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutClassFilter('F3\TestPackage\BasicClass'));
		$firstSubComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutMethodNameFilter('*'));

		$secondSubComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$secondSubComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutClassFilter('F3\TestPackage\BasicClass'));
		$secondSubComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutMethodNameFilter('get*'));

		$expectedPointcutFilterComposite->addFilter('&&', $firstSubComposite);
		$expectedPointcutFilterComposite->addFilter('||', $secondSubComposite);

		$actualPointcutFilterComposite = $this->parser->parse('method(F3\TestPackage\BasicClass->*(..)) || method(F3\TestPackage\BasicClass->get*(..))');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite, 'The filter chain while parsing a simple class expression was not correct.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classTaggedWithDesignatorIsParsedCorrectly() {
		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutClassTaggedWithFilter('someTag'));

		$actualPointcutFilterComposite = $this->parser->parse('classTaggedWith(someTag)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function methodTaggedWithDesignatorIsParsedCorrectly() {
		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutMethodTaggedWithFilter('someTag'));

		$actualPointcutFilterComposite = $this->parser->parse('methodTaggedWith(someTag)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function customFilterDesignatorIsParsedCorrectly() {
		$parser = new \F3\FLOW3\AOP\MockPointcutExpressionParser($this->objectManager);

		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new \F3\FLOW3\Tests\AOP\Fixture\CustomFilter());

		$actualPointcutFilterComposite = $parser->parse('filter(\F3\FLOW3\Tests\AOP\Fixture\CustomFilter)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifACustomFilterDoesNotImplementThePointcutFilterInterfaceAnExceptionIsThrown() {
		$parser = new \F3\FLOW3\AOP\MockPointcutExpressionParser($this->objectManager);

		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new \F3\FLOW3\Tests\AOP\Fixture\CustomFilter());

		try {
			$parser->parse('filter(\F3\FLOW3\Tests\AOP\Fixture\EmptyClass)');
			$this->fail('No exception was thrown.');
		} catch (\Exception  $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function settingFilterDesignatorIsParsedCorrectly() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings['custom']['package']['my']['configuration']['option'] = TRUE;
		$mockConfigurationManager->expects($this->atLeastOnce())->method('getSettings')->will($this->returnValue($settings));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getObject')->with('F3\FLOW3\Configuration\Manager')->will($this->returnValue($mockConfigurationManager));

		$parser = new \F3\FLOW3\AOP\PointcutExpressionParser($mockObjectManager);

		$expectedPointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutSettingFilter($mockConfigurationManager, 'custom: package: my: configuration: option'));

		$actualPointcutFilterComposite = $parser->parse('setting(custom: package: my: configuration: option)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}
}
?>