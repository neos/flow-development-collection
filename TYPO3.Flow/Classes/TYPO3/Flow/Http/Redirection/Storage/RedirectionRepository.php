<?php
namespace TYPO3\Flow\Http\Redirection\Storage;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * Repository for redirection instances.
 * Note: You should not interact with this repository directly. Instead use the RedirectionService!
 *
 * @Flow\Scope("singleton")
 */
class RedirectionRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'sourceUriPath' => QueryInterface::ORDER_ASCENDING,
    );

    /**
     * @param string $sourceUriPath
     * @return Redirection
     */
    public function findOneBySourceUriPath($sourceUriPath)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('sourceUriPathHash', md5(trim($sourceUriPath, '/'))));

        return $query->execute()->getFirst();
    }

    /**
     * @param string $targetUriPath
     * @return Redirection
     */
    public function findOneByTargetUriPath($targetUriPath)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('targetUriPathHash', md5(trim($targetUriPath, '/'))));

        return $query->execute()->getFirst();
    }
}
