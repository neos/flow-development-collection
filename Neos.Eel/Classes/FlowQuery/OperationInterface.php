<?php
namespace Neos\Eel\FlowQuery;

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
 * Contract for a *FlowQuery operation* which is applied onto a set of objects.
 *
 * @api
 */
interface OperationInterface
{
    /**
     * @return string the short name of the operation
     * @api
     */
    public static function getShortName();

    /**
     * @return integer the priority of the operation
     * @api
     */
    public static function getPriority();

    /**
     * @return boolean true if the operation is final, false otherwise
     * @api
     */
    public static function isFinal();

    /**
     * This method is called to determine whether the operation
     * can work with the $context objects. It can be implemented
     * to implement runtime conditions.
     *
     * @param array $context (or array-like object) $context onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     * @api
     */
    public function canEvaluate($context);

    /**
     * Evaluate the operation on the objects inside $flowQuery->getContext(),
     * taking the $arguments into account.
     *
     * The resulting operation results should be stored using $flowQuery->setContext().
     *
     * If the operation is final, evaluate should directly return the operation result.
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return mixed if the operation is final, the return value
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments);
}
