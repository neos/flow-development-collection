<?php
namespace TYPO3\Eel\FlowQuery;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
