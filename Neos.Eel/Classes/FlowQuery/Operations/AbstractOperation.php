<?php
namespace Neos\Eel\FlowQuery\Operations;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\OperationInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Convenience base class for FlowQuery Operations. You should set
 * $shortName and optionally also $final and $priority when subclassing.
 *
 * @api
 */
abstract class AbstractOperation implements OperationInterface
{
    /**
     * The short name of the operation
     *
     * @var string
     * @api
     */
    protected static $shortName = null;

    /**
     * The priority of operations. higher numbers override lower ones.
     *
     * @var integer
     * @api
     */
    protected static $priority = 1;

    /**
     * If TRUE, the operation is final, i.e. directly executed.
     *
     * @var boolean
     * @api
     */
    protected static $final = false;

    /**
     * @return integer the priority of the operation
     * @api
     */
    public static function getPriority()
    {
        return static::$priority;
    }

    /**
     * @return boolean TRUE if the operation is final, FALSE otherwise
     * @api
     */
    public static function isFinal()
    {
        return static::$final;
    }

    /**
     * @return string the short name of the operation
     * @api
     * @throws FlowQueryException
     */
    public static function getShortName()
    {
        if (!is_string(static::$shortName)) {
            throw new FlowQueryException('Short name in class ' . __CLASS__ . ' is empty.', 1332488549);
        }
        return static::$shortName;
    }

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
     * @api
     */
    public function canEvaluate($context)
    {
        return true;
    }
}
