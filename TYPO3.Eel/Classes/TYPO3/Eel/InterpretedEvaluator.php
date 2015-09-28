<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An expression evalutator that interprets expressions
 *
 * There is no generated PHP code so this evaluator does not perform very
 * good in multiple invocations.
 */
class InterpretedEvaluator implements EelEvaluatorInterface
{
    /**
     * Evaluate an expression under a given context
     *
     * @param string $expression
     * @param Context $context
     * @return mixed
     * @throws ParserException
     */
    public function evaluate($expression, Context $context)
    {
        $expression = trim($expression);
        $parser = new InterpretedEelParser($expression, $context);
        $res = $parser->match_Expression();

        if ($res === false) {
            throw new ParserException(sprintf('Expression "%s" could not be parsed.', $expression), 1344514198);
        } elseif ($parser->pos !== strlen($expression)) {
            throw new ParserException(sprintf('Expression "%s" could not be parsed. Error starting at character %d: "%s".', $expression, $parser->pos, substr($expression, $parser->pos)), 1344514188);
        } elseif (!array_key_exists('val', $res)) {
            throw new ParserException(sprintf('Parser error, no val in result %s ', json_encode($res)), 1344514204);
        }

        if ($res['val'] instanceof Context) {
            return $res['val']->unwrap();
        } else {
            return $res['val'];
        }
    }
}
