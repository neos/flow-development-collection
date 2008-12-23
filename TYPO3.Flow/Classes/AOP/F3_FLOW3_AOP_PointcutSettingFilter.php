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
 * @version $Id: F3_FLOW3_AOP_PointcutClassTaggedWithFilter.php 1599 2008-12-10 14:39:10Z k-fish $
 */

/**
 * A settings filter which fires on configuration setting set to TRUE or equal to the given condition.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutClassFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutSettingFilter implements \F3\FLOW3\AOP\PointcutFilterInterface {

	const PATTERN_MATCHVALUEINQUOTES = '/(?:"(?P<DoubleQuotedString>(?:\\"|[^"])*)"|\'(?P<SingleQuotedString>(?:\\\'|[^\'])*)\')/';

	/**
	 * The value of the specified configuration option
	 * @var boolean
	 */
	protected $configurationOption = FALSE;

	/**
	 * The condition value to match against the configuration setting
	 * @var string
	 */
	protected $condition;

	/**
	 * The constructor - initializes the configuration filter with the path to a configuration option
	 *
	 * @param string $configurationExpression The configuration expression (path + optional condition)
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Configuration\Manager $configurationManager, $configurationExpression) {
		$this->parseConfigurationOptionPath($configurationExpression, $configurationManager->getSettings('FLOW3'));
	}

	/**
	 * Checks if the specified configuration option is set to TRUE or FALSE, or if it matches the specified
	 * condition
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class Not needed in this filter
	 * @param \F3\FLOW3\Reflection\ClassReflectionMethod $method Not needed in this filter
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the option is set to TRUE, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier) {
		if (is_bool($this->configurationOption)) {
			return $this->configurationOption;
		} else {
			return ($this->condition === $this->configurationOption);
		}
	}

	/**
	 * Parses the given configuration path expression and sets $this->configurationOption
	 * and $this->condition accordingly
	 *
	 * @param configurationExpression The configuration expression (path + optional condition)
	 * @param array $settings The configuration settings array of the current configuration
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parseConfigurationOptionPath($configurationExpression, $settings) {
		$this->configurationOption = $settings;

		$configurationExpression = split(' *= *', $configurationExpression);
		if (isset($configurationExpression[1])) {
			$matches = array();
			preg_match(self::PATTERN_MATCHVALUEINQUOTES, $configurationExpression[1], $matches);
			if (isset($matches['SingleQuotedString']) && $matches['SingleQuotedString'] !== '') $this->condition = $matches['SingleQuotedString'];
			elseif (isset($matches['DoubleQuotedString']) && $matches['DoubleQuotedString'] !== '') $this->condition = $matches['DoubleQuotedString'];
			else throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('The given condition has a syntax error (Make sure to set quotes correctly). Got: "' . $configurationExpression[1] . '"', 1230047529);
		}

		$configurationKeys = split(':[ ]{0,1}', $configurationExpression[0]);
		foreach ($configurationKeys as $currentKey) {
			if (!isset($this->configurationOption[$currentKey])) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpression('The given configuration path in the pointcut designator "setting" did not exist. Got: "' . $configurationExpression[0] . '"', 1230035614);
			$this->configurationOption = $this->configurationOption[$currentKey];
		}
	}
}

?>