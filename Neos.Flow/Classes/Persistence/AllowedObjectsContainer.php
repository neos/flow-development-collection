<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A container for the list of allowed objects to be persisted during this request.
 *
 * @Flow\Scope("singleton")
 */
final class AllowedObjectsContainer extends \SplObjectStorage
{
    /**
     * @var bool
     */
    protected $checkNext = false;

    /**
     * Set the internal flag to return true for `shouldCheck()` on the next invocation.
     *
     * @param bool $checkNext
     */
    public function checkNext(bool $checkNext = true): void
    {
        $this->checkNext = $checkNext;
    }

    /**
     * Returns true if allowed objects should be checked this time and resets the internal flag to false,
     * so the next call will return false unless `checkNext(true)` is called again.
     *
     * @return bool
     */
    public function shouldCheck(): bool
    {
        $shouldCheck = $this->checkNext;
        $this->checkNext = false;
        return $shouldCheck;
    }
}
