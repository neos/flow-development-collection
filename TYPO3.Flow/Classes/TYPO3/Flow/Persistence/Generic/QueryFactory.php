<?php
namespace TYPO3\Flow\Persistence\Generic;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The QueryFactory used to create queries against the storage backend
 *
 * @api
 */
class QueryFactory implements \TYPO3\Flow\Persistence\Generic\QueryFactoryInterface
{
    /**
     * Creates a query object working on the given class name
     *
     * @param string $className
     * @return \TYPO3\Flow\Persistence\QueryInterface
     * @api
     */
    public function create($className)
    {
        return new \TYPO3\Flow\Persistence\Generic\Query($className);
    }
}
