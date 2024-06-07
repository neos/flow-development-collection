<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Testing\RequestHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\RequestHandlerInterface;

/**
 * A test request handler that does absolutely nothing
 *
 * @Flow\Proxy(false)
 */
final class EmptyRequestHandler implements RequestHandlerInterface
{
    public function handleRequest(): void
    {
    }

    public function canHandleRequest(): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
