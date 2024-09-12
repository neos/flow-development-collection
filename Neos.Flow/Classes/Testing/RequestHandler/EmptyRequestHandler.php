<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Testing\RequestHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Tests\PhpBench\Core\BootstrapBench;

/**
 * A test request handler that does absolutely nothing.
 *
 * Useful for testing a Flow Bootstrap without having run a full boot sequence. E.g. performance of
 * Bootstrap->run() without boot sequence (to compare against with boot sequence).
 * @see BootstrapBench
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
