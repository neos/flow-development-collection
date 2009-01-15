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
 * The pointcut expression parser parses the definition of the place and circumstances
 * where advices can be inserted later on. The input of the parse() function is a string
 * from a pointcut- or advice annotation and returns a pointcut filter composite.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @see \F3\FLOW3\AOP\Pointcut, \F3\FLOW3\AOP\PointcutFilterComposite
 */
class PointcutExpressionParser {

	const PATTERN_SPLITBYOPERATOR = '/\s*(\&\&|\|\|)\s*/';
	const PATTERN_MATCHPOINTCUTDESIGNATOR = '/^\s*(classTaggedWith|class|methodTaggedWith|method|within|filter|setting)/';
	const PATTERN_MATCHVISIBILITYMODIFIER = '/(public|protected)/';

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parse($pointcutExpression) {
		if (!is_string($pointcutExpression) || strlen($pointcutExpression) == 0) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Pointcut expression must be a valid string, ' . gettype($pointcutExpression) . ' given.', 1168874738);

		$pointcutFilterComposite = $this->objectFactory->create('F3\FLOW3\AOP\PointcutFilterComposite');
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
					case 'class' :
					case 'methodTaggedWith' :
					case 'method' :
					case 'within' :
					case 'filter' :
					case 'setting' :
						$parseMethodName = 'parseDesignator' . ucfirst($pointcutDesignator);
						$this->$parseMethodName($operator, $signaturePattern, $pointcutFilterComposite);
					break;
					default :
						throw new \F3\FLOW3\AOP\Exception('Support for pointcut designator "' . $pointcutDesignator . '" has not been implemented (yet).', 1168874740);
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
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class tag filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorClassTaggedWith($operator, $classTagPattern, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutClassTaggedWithFilter', $classTagPattern));
	}

	/**
	 * Takes a class filter pattern and adds a so configured class filter to the
	 * filter composite object.
	 *
	 * @param string $operator The operator
	 * @param string $classPattern The pattern expression as configuration for the class filter
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorClass($operator, $classPattern, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutClassNameFilter', $classPattern));
	}

	/**
	 * Takes a method tag filter pattern and adds a so configured method tag filter to the
	 * filter composite object.
	 *
	 * @param string $operator The operator
	 * @param string $methodTagPattern The pattern expression as configuration for the method tag filter
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the method tag filter) will be added to this composite object.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseDesignatorMethodTaggedWith($operator, $methodTagPattern, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutMethodTaggedWithFilter', $methodTagPattern));
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

		$subComposite = $this->objectFactory->create('F3\FLOW3\AOP\PointcutFilterComposite');
		$subComposite->addFilter('&&', $this->objectFactory->create('F3\FLOW3\AOP\PointcutClassNameFilter', $classPattern));
		$subComposite->addFilter('&&', $this->objectFactory->create('F3\FLOW3\AOP\PointcutMethodNameFilter', $methodNamePattern, $methodVisibility));

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
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutClassTypeFilter', $signaturePattern));
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
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutFilter', $aspectClassName, $pointcutMethodName));
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
		$customFilter = $this->objectManager->getObject($filterObjectName);
		if (!$customFilter instanceof \F3\FLOW3\AOP\PointcutFilterInterface) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Invalid custom filter: "' . $filterObjectName . '" does not implement the required PoincutFilterInterface.', 1231871755);
		$pointcutFilterComposite->addFilter($operator, $customFilter);
	}

	/**
	 * Adds a setting filter to the poincut filter composite
	 *
	 * @param string $operator The operator
	 * @param string $configurationPath The path to the settings option, that should be used
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite: An instance of the pointcut filter composite. The result (ie. the custom filter) will be added to this composite object.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parseDesignatorSetting($operator, $configurationPath, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite) {
		$pointcutFilterComposite->addFilter($operator, $this->objectFactory->create('F3\FLOW3\AOP\PointcutSettingFilter', $configurationPath));
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

		for ($i = $startingPosition; $i < strlen($string); $i++) {
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
			$signaturePattern = trim(substr($signaturePattern, strlen($visibility)));
		}
		return $visibility;
	}
}
?>