<?php
namespace Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\CompilingEelParser;
use Neos\Eel\ParserException;

/**
 * A compiling expression parser
 *
 * The matcher functions will generate PHP code according to the expressions.
 * Method calls and object / array access are wrapped using the Context object.
 */
class EntityPrivilegeExpressionParser extends CompilingEelParser
{
    /**
     * @param array $result
     * @param array $sub
     */
    public function NotExpression_exp(&$result, $sub)
    {
        if (!isset($result['code'])) {
            $result['code'] = '$context';
        }
        $result['code'] .= '->callAndWrap(\'notExpression\', array(' . $this->unwrapExpression($sub['code']) . '))';
    }

    /**
     * @param array $result
     * @param array $sub
     */
    public function Disjunction_rgt(&$result, $sub)
    {
        $result['code'] = '$context->callAndWrap(\'disjunction\', array(' . $this->unwrapExpression($result['code']) . ', ' . $this->unwrapExpression($sub['code']) . '))';
    }

    /**
     * @param array $result
     * @param array $sub
     */
    public function Conjunction_rgt(&$result, $sub)
    {
        $result['code'] = '$context->callAndWrap(\'conjunction\', array(' . $this->unwrapExpression($result['code']) . ', ' . $this->unwrapExpression($sub['code']) . '))';
    }

    /**
     * @param array $result
     * @param array $sub
     * @throws ParserException
     */
    public function Comparison_rgt(&$result, $sub)
    {
        $lval = $result['code'];
        $rval = $sub['code'];

        if (strpos($lval, '$context->callAndWrap(\'property\'') === false) {
            $temp = $rval;
            $rval = $lval;
            $lval = $temp;
        }

        switch ($result['comp']) {
            case '==':
                $compMethod = 'equals';
                break;
            case '!=':
                $compMethod = 'notEquals';
                break;
            case '<':
                $compMethod = 'lessThan';
                break;
            case '<=':
                $compMethod = 'lessOrEqual';
                break;
            case '>':
                $compMethod = 'greaterThan';
                break;
            case '>=':
                $compMethod = 'greaterOrEqual';
                break;
            default:
                throw new ParserException('Unexpected comparison operator "' . $result['comp'] . '"', 1344512571);
        }

        $result['code'] = $lval . '->callAndWrap(\'' . $compMethod . '\', array(' . $this->unwrapExpression($rval) . '))';
    }
}
