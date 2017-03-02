<?php
namespace Neos\Flow\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Configuration\ConfigurationManager;

/**
 * A settings filter which fires on configuration setting set to TRUE or equal to the given condition.
 *
 * Example: setting(FooPackage.configuration.option = 'AOP is cool')
 *
 * @Flow\Proxy(false)
 */
class PointcutSettingFilter implements PointcutFilterInterface
{
    const PATTERN_SPLITBYEQUALSIGN = '/\s*( *= *)\s*/';
    const PATTERN_MATCHVALUEINQUOTES = '/(?:"(?P<DoubleQuotedString>(?:\\"|[^"])*)"|\'(?P<SingleQuotedString>(?:\\\'|[^\'])*)\')/';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * The path leading to the setting to match with
     * @var string
     */
    protected $settingComparisonExpression;

    /**
     * The value of the specified setting
     * @var mixed
     */
    protected $actualSettingValue;

    /**
     * The condition value to match against the configuration setting
     * @var mixed
     */
    protected $condition;

    /**
     * @var boolean
     */
    protected $cachedResult;

    /**
     * The constructor - initializes the configuration filter with the path to a configuration option
     *
     * @param string $settingComparisonExpression Path (and optional condition) leading to the setting
     */
    public function __construct($settingComparisonExpression)
    {
        $this->settingComparisonExpression = $settingComparisonExpression;
    }

    /**
     * Injects the configuration manager
     *
     * @param ConfigurationManager $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
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
     */
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        if ($this->cachedResult === null) {
            $this->cachedResult = (is_bool($this->actualSettingValue)) ? $this->actualSettingValue : ($this->condition === $this->actualSettingValue);
        }
        return $this->cachedResult;
    }

    /**
     * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
     *
     * @return boolean TRUE if this filter has runtime evaluations
     */
    public function hasRuntimeEvaluationsDefinition()
    {
        return false;
    }

    /**
     * Returns runtime evaluations for the pointcut.
     *
     * @return array Runtime evaluations
     */
    public function getRuntimeEvaluationsDefinition()
    {
        return [];
    }

    /**
     * Parses the given configuration path expression and sets $this->actualSettingValue
     * and $this->condition accordingly
     *
     * @param string $settingComparisonExpression The configuration expression (path + optional condition)
     * @return void
     * @throws InvalidPointcutExpressionException
     */
    protected function parseConfigurationOptionPath($settingComparisonExpression)
    {
        $settingComparisonExpression = preg_split(self::PATTERN_SPLITBYEQUALSIGN, $settingComparisonExpression);
        if (isset($settingComparisonExpression[1])) {
            $matches = [];
            preg_match(self::PATTERN_MATCHVALUEINQUOTES, $settingComparisonExpression[1], $matches);
            if (isset($matches['SingleQuotedString']) && $matches['SingleQuotedString'] !== '') {
                $this->condition = $matches['SingleQuotedString'];
            } elseif (isset($matches['DoubleQuotedString']) && $matches['DoubleQuotedString'] !== '') {
                $this->condition = $matches['DoubleQuotedString'];
            } else {
                throw new InvalidPointcutExpressionException('The given condition has a syntax error (Make sure to set quotes correctly). Got: "' . $settingComparisonExpression[1] . '"', 1230047529);
            }
        }

        $configurationKeys = preg_split('/\./', $settingComparisonExpression[0]);

        if (count($configurationKeys) > 0) {
            $settingPackageKey = array_shift($configurationKeys);
            $settingValue = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingPackageKey);
            foreach ($configurationKeys as $currentKey) {
                if (!isset($settingValue[$currentKey])) {
                    throw new InvalidPointcutExpressionException('The given configuration path in the pointcut designator "setting" did not exist. Got: "' . $settingComparisonExpression[0] . '"', 1230035614);
                }
                $settingValue = $settingValue[$currentKey];
            }
            $this->actualSettingValue = $settingValue;
        }
    }

    /**
     * This method is used to optimize the matching process.
     *
     * @param ClassNameIndex $classNameIndex
     * @return ClassNameIndex
     */
    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        return $classNameIndex;
    }
}
