<?php
namespace TYPO3\Flow\Persistence\Generic;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A persistence query factory interface
 *
 * @api
 */
interface QueryFactoryInterface
{
    /**
     * Creates a query object working on the given class name
     *
     * @param string $className
     * @return \TYPO3\Flow\Persistence\QueryInterface
     * @api
     */
    public function create($className);
}
