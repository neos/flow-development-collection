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

use Neos\Eel\Utility\DefaultContextConfiguration;

/**
 * Utility to reduce boilerplate code needed to set default context variables and evaluate a string that possibly is an EEL expression.
 *
 */
class Utility
{
    /**
     * Return the expression if it is a valid EEL expression, null otherwise.
     */
    public static function parseEelExpression(string $expression): ?string
    {
        if (!str_starts_with($expression, '${')) {
            return null;
        }
        return match (preg_match(Package::EelExpressionRecognizer, $expression, $matches)) {
            1 => $matches['exp'],
            default => null
        };
    }

    /**
     * Get variables from configuration that should be set in the context by default.
     * For example Eel helpers are made available by this.
     *
     * @param array $configuration An one dimensional associative array of context variable paths mapping to object names
     * @return array Array with default context variable objects.
     * @deprecated with Neos8.3 use {@see createDefaultProtectedContextFromConfiguration} instead
     */
    public static function getDefaultContextVariables(array $configuration)
    {
        $flattenedLegacyConfig = $configuration;

        if (isset($configuration["__internalLegacyConfig"])) {
            unset($configuration["__internalLegacyConfig"]);
            $flattenedLegacyConfig = array_merge($configuration, $flattenedLegacyConfig["__internalLegacyConfig"]);
        }

        $defaultContextVariables = [];
        foreach ($flattenedLegacyConfig as $variableName => $objectType) {
            if (is_array($objectType)) {
                // silently pass new structure where $objectType is an array with "className" as key or where $objectType might hold further nested helpers as array
                // see DefaultContextConfiguration::fromConfiguration($configuration)
                continue;
            }
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
     */
    public static function createDefaultProtectedContextFromConfiguration(array $configuration): ProtectedContext
    {
        return ProtectedContext::fromDefaultContextConfiguration(
            DefaultContextConfiguration::fromConfiguration($configuration)
        );
    }

    /**
     * Evaluate an Eel expression.
     *
     * @throws Exception
     */
    public static function evaluateEelExpression(
        string $expression,
        EelEvaluatorInterface $eelEvaluator,
        ProtectedContext|array $contextVariables,
        array $defaultContextConfiguration = []
    ): mixed {
        $eelExpression = self::parseEelExpression($expression);
        if ($eelExpression === null) {
            throw new Exception(
                'The EEL expression "' . $expression . '" was not a valid EEL expression. Perhaps you forgot to wrap it in ${...}?',
                1410441849
            );
        }

        $defaultContextVariables = self::createDefaultProtectedContextFromConfiguration($defaultContextConfiguration);

        $context = $defaultContextVariables->union(
            $contextVariables instanceof ProtectedContext
                ? $contextVariables
                : new ProtectedContext($contextVariables)
        );

        if (is_array($contextVariables)) {
            // legacy
            // allow functions on the uppermost context level to allow calling them without
            // implementing ProtectedContextAwareInterface which is impossible for functions
            // this legacy case will happen, if one used the legacy getDefaultContextVariables() method
            // instead of $defaultContextConfiguration and passed the $contextVariables as array,
            // which only returns a simple array without information about allowed methods
            foreach ($contextVariables as $key => $value) {
                if (is_callable($value)) {
                    $context->allow($key);
                }
            }
        }

        return $eelEvaluator->evaluate($eelExpression, $context);
    }
}
