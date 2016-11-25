<?php
namespace Neos\Flow\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\QueryInterface;

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
     * @return QueryInterface
     * @api
     */
    public function create($className);
}
