<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * The pointcut expression parser parses the definition of the place and circumstances
 * where advices can be inserted later on. The input of the parse() function is a string
 * from a pointcut- or advice annotation and returns a pointcut filter composite.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutExpressionParser.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see \F3\FLOW3\AOP\Pointcut, \F3\FLOW3\AOP\PointcutFilterComposite
 */
class PointcutExpressionParser {

	const PATTERN_SPLITBYOPERATOR = '/\s*(\&\&|\|\|)\s*/';
	const PATTERN_MATCHPOINTCUTDESIGNATOR = '/^\s*(classTaggedWith|class|method|within|filter)/';
	const PATTERN_MATCHVISIBILITYMODIFIER = '/(public|protected|private)/';

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface $objectManager
	 */
	protected $objectManager;

	/**
	 * Constructs this expression parser
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Parses a string pointcut expression and returns the pointcut
	 * objects accordingly
	 *
	 * @param string $poincutExpression The expression defining the pointcut
	 * @return \F3\FLOW3\AOP\PointcutFilterComposite A composite of class-filters, method-filters and pointcuts
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parse($pointcutExpression) {
		if (!is_string($pointcutExpression) || \F3\PHP6\Functions::strlen($pointcutExpression) == 0) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Pointcut expression must be a valid string, ' . gettype($pointcutExpression) . ' given.', 1168874738);

		$pointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$pointcutExpressionParts = preg_split(self::PATTERN_SPLITBYOPERATOR, $pointcutExpression, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($partIndex = 0; $partIndex < count($pointcutExpressionParts); $partIndex += 2) {
			$operator = ($partIndex > 0) ? trim($pointcutExpressionParts[$partIndex - 1]) : '&&';
			$expression = trim($pointcutExpressionParts[$partIndex]);

			if ($expression{0} == '!') {
				$expression = trim(substr($expression, 1));
				$operator .= '!';
			}

			if (strpos($expression, '(') === FALSE) {
				$this->parseDesignatorPointcut($operator, $expression, $pointcutFilterComposite);
			} else {
				$matches = array();
				$numberOfMatches = preg_match(self::PATTERN_MATCHPOINTCUTDESIGNATOR, $expression, $matches);
				if ($numberOfMatches !== 1) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Syntax error: Pointcut designator expected near "' . $expression . '"', 1168874739);
				$pointcutDesignator = $matches[0];
				$signaturePattern = $this->getSubstringBetweenParentheses($expression);
				switch ($pointcutDesignator) {
					case 'classTaggedWith' :
						$this->parseDesignatorClassTaggedWith($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					case 'class' :
						$this->parseDesignatorClass($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					case 'method' :
						$this->parseDesignatorMethod($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					case 'within' :
						$this->parseDesignatorWithin($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					case 'filter' :
						$this->parseDesignatorFilter($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					default :
						throw new \RuntimeException('Support for pointcut designator "' . $pointcutDesignator . '" has not been implemented (yet).', 1168874740);
				}
			}
		}
		return $pointcutFilterComposite;
	}

	/**
	 * Takes a class tag filter pattern and adds a so configured class tag filter to the
	 * filter composite object.
	 *
	 * @param string $operator The operator
	 * @param string $classTagPattern The pattern expression as configuration for the class tag filter
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite &$pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class tag filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorClassTaggedWith($operator, $classTagPattern, \F3\FLOW3\AOP\PointcutFilterComposite &$pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, new \F3\FLOW3\AOP\PointcutClassTaggedWithFilter($classTagPattern));
	}

	/**
	 * Takes a class filter pattern and adds a so configured class filter to the
	 * filter composite object.
	 *
	 * @param string $operator The operator
	 * @param string $classPattern The pattern expression as configuration for the class filter
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite &$pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorClass($operator, $classPattern, \F3\FLOW3\AOP\PointcutFilterComposite &$pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, new \F3\FLOW3\AOP\PointcutClassFilter($classPattern));
	}

	/**
	 * Splits the parameters of the pointcut designator "method" into a class
	 * and a method part and adds the appropriately configured filters to the
	 * filter composite object.
	 *
	 * @param string $operator The operator
	 * @param string $signaturePattern The pattern expression defining the class and method - the "signature"
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class and method filter) will be added to this composite object.
	 * @return void
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression if there's an error in the pointcut expression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorMethod($operator, $signaturePattern, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		if (strpos($signaturePattern, '->') === FALSE) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Syntax error: "->" expected in "' . $signaturePattern . '".', 1169027339);
		$methodVisibility = $this->getVisibilityFromSignaturePattern($signaturePattern);
		list($classPattern, $methodPattern) = explode ('->', $signaturePattern, 2);
		if (strpos($methodPattern, '(') === FALSE ) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Syntax error: "(" expected in "' . $methodPattern . '".', 1169144299);

		$methodNamePattern = substr($methodPattern, 0, (strpos($methodPattern, '(')));

		$subComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$subComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutClassFilter($classPattern));
		$subComposite->addFilter('&&', new \F3\FLOW3\AOP\PointcutMethodNameFilter($methodNamePattern, $methodVisibility));

		$pointcutFilterComposite->addFilter($operator, $subComposite);
	}

	/**
	 * Adds a class type filter to the poincut filter composite
	 *
	 * @param string $signaturePattern The pattern expression defining the class type
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite: An instance of the pointcut filter composite. The result (ie. the class type filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorWithin($operator, $signaturePattern, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, new \F3\FLOW3\AOP\PointcutClassTypeFilter($signaturePattern));
	}

	/**
	 * Splits the value of the pointcut designator "pointcut" into an aspect
	 * class- and a pointcut method part and adds the appropriately configured
	 * filter to the composite object.
	 *
	 * @param string $operator The operator
	 * @param string $pointcutExpression The pointcut expression (value of the designator)
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite: An instance of the pointcut filter composite. The result (ie. the pointcut filter) will be added to this composite object.
	 * @return void
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorPointcut($operator, $pointcutExpression, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		if (strpos($pointcutExpression, '->') === FALSE) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Syntax error: "->" expected in "' . $pointcutExpression . '".', 1172219205);
		list($aspectClassName, $pointcutMethodName) = explode ('->', $pointcutExpression, 2);
		$pointcutFilterComposite->addFilter($operator, $this->objectManager->getObject('F3\FLOW3\AOP\PointcutFilter', $aspectClassName, $pointcutMethodName));
	}

	/**
	 * Adds a custom filter to the poincut filter composite
	 *
	 * @param string $operator The operator
	 * @param string $filterObjectName Object Name of the custom filter (value of the designator)
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite: An instance of the pointcut filter composite. The result (ie. the custom filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorFilter($operator, $filterObjectName, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, $this->createCustomFilter($filterObjectName));
	}

	/**
	 * Returns the substring of $string which is enclosed by parentheses
	 * of the first level.
	 *
	 * @param string $string The string to parse
	 * @return string The inner part between the first level of parentheses
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getSubstringBetweenParentheses($string) {
		$startingPosition = 0;
		$openParentheses = 0;
		$substring = '';

		for ($i = $startingPosition; $i < \F3\PHP6\Functions::strlen($string); $i++) {
			if ($string{$i} == ')') $openParentheses--;
			if ($openParentheses > 0) $substring .= $string{$i};
			if ($string{$i} == '(') $openParentheses++;
		}
		if ($openParentheses > 0) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Pointcut expression is in excess of ' . $openParentheses . ' closing parentheses.', 1168966689);
		if ($openParentheses < 0) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Pointcut expression lacks of ' . $openParentheses . ' closing parentheses.', 1168966690);
		return $substring;
	}

	/**
	 * Parses the signature pattern and returns the visibility modifier if any. If a modifier
	 * was found, it will be removed from the $signaturePattern.
	 *
	 * @param string &$signaturePattern The regular expression for matching the method() signature
	 * @return string Visibility modifier or NULL of none was found
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getVisibilityFromSignaturePattern(&$signaturePattern) {
		$visibility = NULL;
		$matches = array();
		$numberOfMatches = preg_match_all(self::PATTERN_MATCHVISIBILITYMODIFIER, $signaturePattern, $matches, PREG_SET_ORDER);
		if ($numberOfMatches > 1) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Syntax error: method name expected after visibility modifier in "' . $signaturePattern . '".', 1172492754);
		if ($numberOfMatches === FALSE) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Error while matching visibility modifier in "' . $signaturePattern . '".', 1172492967);
		if ($numberOfMatches === 1) {
			$visibility = $matches[0][0];
			$signaturePattern = trim(substr($signaturePattern, \F3\PHP6\Functions::strlen($visibility)));
		}
		return $visibility;
	}

	/**
	 * Factory method for creating custom filter instances
	 *
	 * @param string Object name of the filter
	 * @return object An instance of the filter object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createCustomFilter($filterObjectName) {
		return $this->objectManager->getObject($filterObjectName);
	}
}
?>