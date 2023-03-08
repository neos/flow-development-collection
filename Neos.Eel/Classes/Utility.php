<?php
namespace Neos\Eel;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Utility to reduce boilerplate code needed to set default context variables and evaluate a string that possibly is an EEL expression.
 *
 */
class Utility
{
    /**
     * Return the expression if it is an valid EEL expression, otherwise return null.
     *
     * @param string $expression
     * @return string|null
     */
    public static function parseEelExpression($expression)
    {
        return preg_match(Package::EelExpressionRecognizer, $expression, $matches) === 1 ? $matches['exp'] : null;
    }

    /**
     * Get variables from configuration that should be set in the context by default.
     * For example Eel helpers are made available by this.
     *
     * @param array $configuration An one dimensional associative array of context variable paths mapping to object names
     * @return array Array with default context variable objects.#
     * @deprecated with Neos8.3 use {@see createDefaultProtectedContextFromConfiguration} instead
     */
    public static function getDefaultContextVariables(array $configuration)
    {
        $defaultContextVariables = [];
        foreach ($configuration as $variableName => $objectType) {
            $currentPathBase = &$defaultContextVariables;
            $variablePathNames = explode('.', $variableName);
            foreach ($variablePathNames as $pathName) {
                if (!isset($currentPathBase[$pathName])) {
                    $currentPathBase[$pathName] = [];
                }
                $currentPathBase = &$currentPathBase[$pathName];
            }

            if (str_contains($objectType, '::')) {
                if (str_contains($variableName, '.')) {
                    throw new Exception(sprintf('Function helpers are only allowed on root level, "%s" was given?', $variableName), 1557911015);
                }
                $currentPathBase = \Closure::fromCallable($objectType);
            } else {
                $currentPathBase = new $objectType();
            }
        }
        return $defaultContextVariables;
    }

    /**
     * Create default ProtectedContext from configuration
     * For example Eel helpers are made available by this.
     *
     * @param array{string: class-string|string|array{"className": class-string, "allowedMethods"?: string}} $configuration
     * @return ProtectedContext with an array of default context variable objects.
     * @throws Exception
     */
    public static function createDefaultProtectedContextFromConfiguration(array $configuration): ProtectedContext
    {
        $allowed = [];
        $defaultContextVariables = [];

        foreach ($configuration as $variableName => $objectType) {
            $currentPathBase = &$defaultContextVariables;
            $variablePathNames = explode('.', $variableName);
            foreach ($variablePathNames as $pathName) {
                if (!isset($currentPathBase[$pathName])) {
                    $currentPathBase[$pathName] = [];
                }
                $currentPathBase = &$currentPathBase[$pathName];
            }

            if (is_array($objectType)) {
                $className = $objectType["className"];
                if (isset($objectType["allowedMethods"])) {
                    $allowed[] = $variableName . '.' . $objectType["allowedMethods"];
                }
                $currentPathBase = new $className();
            } elseif (str_contains($objectType, '::')) {
                // Allow functions on the uppermost context level to allow calling them without
                // implementing ProtectedContextAwareInterface which is impossible for functions
                $allowed[] = $variableName;
                if (str_contains($variableName, '.')) {
                    throw new Exception(sprintf('Function helpers are only allowed on root level, "%s" was given', $variableName), 1557911015);
                }
                $currentPathBase = \Closure::fromCallable($objectType);
            } else {
                $currentPathBase = new $objectType();
            }
        }

        $defaultContext = new ProtectedContext($defaultContextVariables);

        foreach ($allowed as $allow) {
            $defaultContext->allow($allow);
        }

        return $defaultContext;
    }

    /**
     * Evaluate an Eel expression.
     *
     * @param string $expression
     * @param EelEvaluatorInterface $eelEvaluator
     * @param array $contextVariables
     * @param array $defaultContextConfiguration
     * @return mixed
     * @throws Exception
     */
    public static function evaluateEelExpression($expression, EelEvaluatorInterface $eelEvaluator, array $contextVariables, array $defaultContextConfiguration = [])
    {
        $eelExpression = self::parseEelExpression($expression);
        if ($eelExpression === null) {
            throw new Exception('The EEL expression "' . $expression . '" was not a valid EEL expression. Perhaps you forgot to wrap it in ${...}?', 1410441849);
        }

        $defaultContextVariables = self::createDefaultProtectedContextFromConfiguration($defaultContextConfiguration);

        $context = $defaultContextVariables->union(
            new ProtectedContext($contextVariables)
        );

        return $eelEvaluator->evaluate($eelExpression, $context);
    }
}
