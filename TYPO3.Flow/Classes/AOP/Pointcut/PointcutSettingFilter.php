<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Pointcut;

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
 * A settings filter which fires on configuration setting set to TRUE or equal to the given condition.
 *
 * Example: setting(FooPackage.configuration.option = 'AOP is cool')
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PointcutSettingFilter implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	const PATTERN_SPLITBYEQUALSIGN = '/\s*( *= *)\s*/';
	const PATTERN_MATCHVALUEINQUOTES = '/(?:"(?P<DoubleQuotedString>(?:\\"|[^"])*)"|\'(?P<SingleQuotedString>(?:\\\'|[^\'])*)\')/';

	/**
	 * The path leading to the setting to match with
	 * @var string
	 */
	protected $settingComparisonExpression;

	/**
	 * The value of the specified setting
	 * @var mixed
	 */
	protected $actualSettingValue = FALSE;

	/**
	 * The condition value to match against the configuration setting
	 * @var mixed
	 */
	protected $condition;

	/**
	 * The constructor - initializes the configuration filter with the path to a configuration option
	 *
	 * @param string $settingComparisonExpression Path (and optional condition) leading to the setting
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($settingComparisonExpression) {
		$this->settingComparisonExpression = $settingComparisonExpression;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Initializes this filter
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->parseConfigurationOptionPath($this->settingComparisonExpression);
	}

	/**
	 * Checks if the specified configuration option is set to TRUE or FALSE, or if it matches the specified
	 * condition
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in - not used here
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if (is_bool($this->actualSettingValue)) {
			return $this->actualSettingValue;
		} else {
			return ($this->condition === $this->actualSettingValue);
		}
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return FALSE;
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsDefinition() {
		return array();
	}

	/**
	 * Parses the given configuration path expression and sets $this->actualSettingValue
	 * and $this->condition accordingly
	 *
	 * @param string settingComparisonExpression The configuration expression (path + optional condition)
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function parseConfigurationOptionPath($settingComparisonExpression) {
		$settingComparisonExpression = preg_split(self::PATTERN_SPLITBYEQUALSIGN, $settingComparisonExpression);
		if (isset($settingComparisonExpression[1])) {
			$matches = array();
			preg_match(self::PATTERN_MATCHVALUEINQUOTES, $settingComparisonExpression[1], $matches);
			if (isset($matches['SingleQuotedString']) && $matches['SingleQuotedString'] !== '') {
				$this->condition = $matches['SingleQuotedString'];
			} elseif (isset($matches['DoubleQuotedString']) && $matches['DoubleQuotedString'] !== '') {
				$this->condition = $matches['DoubleQuotedString'];
			} else {
				throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException('The given condition has a syntax error (Make sure to set quotes correctly). Got: "' . $settingComparisonExpression[1] . '"', 1230047529);
			}
		}

		$configurationKeys = preg_split('/\./', $settingComparisonExpression[0]);

		if (count($configurationKeys) > 0) {
			$settingPackageKey = array_shift($configurationKeys);
			$settingValue = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingPackageKey);
			foreach ($configurationKeys as $currentKey) {
				if (!isset($settingValue[$currentKey])) throw new \F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException('The given configuration path in the pointcut designator "setting" did not exist. Got: "' . $settingComparisonExpression[0] . '"', 1230035614);
				$settingValue = $settingValue[$currentKey];
			}
			$this->actualSettingValue = $settingValue;
		}
	}
}

?>