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
     * Get variables from configuration that should be set in the context by default.
     * For example Eel helpers are made available by this.
     *
     * @param array $configuration An one dimensional associative array of context variable paths mapping to object names
     * @return array Array with default context variable objects.
     */
    public static function getDefaultContextVariables(array $configuration)
    {
        $defaultContextVariables = [];
        foreach ($configuration as $variableName => $objectType) {
            $currentPathBase = & $defaultContextVariables;
            $variablePathNames = explode('.', $variableName);
            foreach ($variablePathNames as $pathName) {
                if (!isset($currentPathBase[$pathName])) {
                    $currentPathBase[$pathName] = [];
                }
                $currentPathBase = & $currentPathBase[$pathName];
            }
            $currentPathBase = new $objectType();
        }

        return $defaultContextVariables;
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
        $matches = null;
        if (!preg_match(Package::EelExpressionRecognizer, $expression, $matches)) {
            throw new Exception('The EEL expression "' . $expression . '" was not a valid EEL expression. Perhaps you forgot to wrap it in ${...}?', 1410441849);
        }

        $defaultContextVariables = self::getDefaultContextVariables($defaultContextConfiguration);
        $contextVariables = array_merge($defaultContextVariables, $contextVariables);

        if (isset($contextVariables['q'])) {
            throw new Exception('Context variable "q" not allowed, as it is already reserved for FlowQuery use.', 1410441819);
        }

        $contextVariables['q'] = function ($element) {
            return new FlowQuery\FlowQuery(is_array($element) || $element instanceof \Traversable ? $element : [$element]);
        };

        $context = new ProtectedContext($contextVariables);
        $context->whitelist('q');

        return $eelEvaluator->evaluate($matches['exp'], $context);
    }
}
