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

use Neos\Flow\Annotations as Flow;

/**
 * FlowQuery Operation Resolver Interface
 */
interface OperationResolverInterface
{
    /**
     * @param string $operationName
     * @return boolean TRUE if $operationName is final
     */
    public function isFinalOperation($operationName);

    /**
     * Resolve an operation, taking runtime constraints into account.
     *
     * @param string      $operationName
     * @param array|mixed $context
     * @return OperationInterface the resolved operation
     */
    public function resolveOperation($operationName, $context);

    /**
     * @param string $operationName
     * @return boolean
     */
    public function hasOperation($operationName);
}
