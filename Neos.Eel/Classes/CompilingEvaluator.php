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

use Neos\Cache\Frontend\StringFrontend;

/**
 * An evaluator that compiles expressions down to PHP code
 *
 * This simple implementation will lazily parse and evaluate the generated PHP
 * code into a function with a name built from the hashed expression.
 *
 */
class CompilingEvaluator implements EelEvaluatorInterface
{
    /**
     * Runtime cache of execution ready closures.
     *
     * @var \closure[]
     */
    protected $evaluatedExpressions = [];

    /**
     * @var StringFrontend
     */
    protected $expressionCache;

    /**
     * TODO: As soon as we support PSR-16 (Simple Cache) this could be replaced by a simple cache.
     *
     * @param StringFrontend $expressionCache
     */
    public function injectExpressionCache(StringFrontend $expressionCache)
    {
        $this->expressionCache = $expressionCache;
    }

    /**
     * Evaluate an expression under a given context
     *
     * @param string $expression
     * @param Context $context
     * @return mixed
     */
    public function evaluate($expression, Context $context)
    {
        $expression = trim($expression);
        $identifier = md5($expression);
        $functionName = 'expression_' . $identifier;
        if (isset($this->evaluatedExpressions[$functionName])) {
            return $this->evaluateAndUnwrap($this->evaluatedExpressions[$functionName], $context);
        }

        $functionDeclaration = $this->expressionCache->get($functionName);
        if (!$functionDeclaration) {
            $functionDeclaration = $this->generateEvaluatorCode($expression);
            $this->expressionCache->set($functionName, $functionDeclaration);
        }

        $expressionFunction = eval($functionDeclaration);
        $this->evaluatedExpressions[$functionName] = $expressionFunction;
        return $this->evaluateAndUnwrap($expressionFunction, $context);
    }

    /**
     * @param \closure $expressionFunction
     * @param Context $context
     * @return mixed
     */
    protected function evaluateAndUnwrap(\closure $expressionFunction, Context $context)
    {
        $result = $expressionFunction($context);
        if ($result instanceof Context) {
            return $result->unwrap();
        }

        return $result;
    }

    /**
     * Internal generator method
     *
     * Used by unit tests to debug generated PHP code.
     *
     * @param string $expression
     * @return string
     * @throws ParserException
     */
    protected function generateEvaluatorCode($expression)
    {
        $parser = new CompilingEelParser($expression);
        $result = $parser->match_Expression();

        if ($result === false) {
            throw new ParserException(sprintf('Expression "%s" could not be parsed.', $expression), 1344513194);
        } elseif ($parser->pos !== strlen($expression)) {
            throw new ParserException(sprintf('Expression "%s" could not be parsed. Error starting at character %d: "%s".', $expression, $parser->pos, substr($expression, $parser->pos)), 1327682383);
        } elseif (!array_key_exists('code', $result)) {
            throw new ParserException(sprintf('Parser error, no code in result %s ', json_encode($result)), 1334491498);
        }

        return 'return function ($context) {return ' . $result['code'] . ';};';
    }
}
