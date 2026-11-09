<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class CleanupObject
{
    /**
     * @var bool
     */
    protected $state = false;

    public function toggleState(): void
    {
        $this->state = !$this->state;
    }

    /**
     * @return bool
     */
    public function getState(): bool
    {
        return $this->state;
    }
}
