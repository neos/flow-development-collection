<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\ACL;

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
 * @subpackage Security
 * @version $Id:$
 */

/**
 *
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PolicyExpressionParser extends \F3\FLOW3\AOP\PointcutExpressionParser {

	/**
	 * @var array The resources array from the configuration.
	 */
	protected $resourcesTree = array();

	/**
	 * Default constructor
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		parent::__construct($objectManager);
	}

	/**
	 * Sets the resource array that should be parsed
	 *
	 * @param array The resources array from the configuration.
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setResourcesTree($resourcesTree) {
		$this->resourcesTree = $resourcesTree;
	}

	/**
	 * Extension of the parse function: Adds a circular reference detection to the parse function.
	 *
	 * @param string $pointcutExpression The pointcut expression to parse
	 * @param array $trace A trace of all visited pointcut expression, used for circular reference detection
	 * @return \F3\FLOW3\AOP\PointcutFilterComposite A composite of class-filters, method-filters and pointcuts
	 * @throws \F3\FLOW3\Security\Exception\CircularResourceDefinitionDetected
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function parse($pointcutExpression, $trace = array()) {
		if (!is_string($pointcutExpression) || \F3\PHP6\Functions::strlen($pointcutExpression) == 0) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('Pointcut expression must be a valid string, ' . gettype($pointcutExpression) . ' given.', 1168874738);

		$pointcutFilterComposite = new \F3\FLOW3\AOP\PointcutFilterComposite();
		$pointcutExpressionParts = preg_split(parent::PATTERN_SPLITBYOPERATOR, $pointcutExpression, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($partIndex = 0; $partIndex < count($pointcutExpressionParts); $partIndex += 2) {
			$operator = ($partIndex > 0) ? trim($pointcutExpressionParts[$partIndex - 1]) : '&&';
			$expression = trim($pointcutExpressionParts[$partIndex]);

			if ($expression{0} == '!') {
				$expression = trim(substr($expression, 1));
				$operator .= '!';
			}

			if (strpos($expression, '(') === FALSE) {
				if (in_array($expression, $trace)) throw new \F3\FLOW3\Security\Exception\CircularResourceDefinitionDetected('A circular reference was detected in the security policy resources definition. Look near: ' . $expression, 1222028842);
				$trace[] = $expression;
				$this->parseDesignatorPointcut($operator, $expression, $pointcutFilterComposite, $trace);
			}
		}

		return parent::parse($pointcutExpression);
	}

	/**
	 * Walks recursively through the resources tree.
	 *
	 * @param string $operator The operator
	 * @param string $pointcutExpression The pointcut expression (value of the designator)
	 * @param \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite: An instance of the pointcut filter composite. The result (ie. the pointcut filter) will be added to this composite object.
	 * @return void
	 * @throws \F3\FLOW3\AOP\Exception\InvalidPointcutExpression
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parseDesignatorPointcut($operator, $pointcutExpression, \F3\FLOW3\AOP\PointcutFilterComposite $pointcutFilterComposite, $trace = array()) {
		if (!isset($this->resourcesTree[$pointcutExpression])) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('The given resource was not defined: ' . $pointcutExpression . '".', 1222014591);

		$pointcutFilterComposite->addFilter($operator, $this->parse($this->resourcesTree[$pointcutExpression], $trace));
	}
}
?>