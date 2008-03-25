<?php
declare(ENCODING = 'utf-8');

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
 * Testcase for the default AOP Pointcut Expression Parser implementation
 * 
 * @package		FLOW3
 * @version 	$Id:F3_FLOW3_AOP_PointcutExpressionParserTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_PointcutExpressionParserTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_AOP_PointcutExpressionParser
	 */
	protected $parser;
	
	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->parser = $this->componentManager->getComponent('F3_FLOW3_AOP_PointcutExpressionParser');
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
		} catch (Exception $exception) {
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
			$this->parser->parse('method(F3_TestPackage_BasicClass->*(..)) || ())');
		} catch (Exception $exception) {
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
			$this->parser->parse('method(F3_TestPackage_BasicClass)');
		} catch (Exception $exception) {
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
		$expectedPointcutFilterComposite = new F3_FLOW3_AOP_PointcutFilterComposite();
		
		$firstSubComposite = new F3_FLOW3_AOP_PointcutFilterComposite();
		$firstSubComposite->addFilter('&&', new F3_FLOW3_AOP_PointcutClassFilter('F3_TestPackage_BasicClass'));
		$firstSubComposite->addFilter('&&', new F3_FLOW3_AOP_PointcutMethodNameFilter('*'));
		
		$secondSubComposite = new F3_FLOW3_AOP_PointcutFilterComposite();
		$secondSubComposite->addFilter('&&', new F3_FLOW3_AOP_PointcutClassFilter('F3_TestPackage_BasicClass'));
		$secondSubComposite->addFilter('&&', new F3_FLOW3_AOP_PointcutMethodNameFilter('get*'));
		
		$expectedPointcutFilterComposite->addFilter('&&', $firstSubComposite);
		$expectedPointcutFilterComposite->addFilter('||', $secondSubComposite);
		
		$actualPointcutFilterComposite = $this->parser->parse('method(F3_TestPackage_BasicClass->*(..)) || method(F3_TestPackage_BasicClass->get*(..))');	
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite, 'The filter chain while parsing a simple class expression was not correct.');
	}
}
?>