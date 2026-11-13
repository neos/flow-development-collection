<?php

declare(strict_types=1);

namespace Neos\Flow\ResourceManagement\Target;

/**
 * Yielded by the implementation of an {@see TargetInterface} after each resource publishing
 */
final readonly class ResourcePublishResult
{
    public function __construct(
        public int $iteration,
        public string $message,
        public mixed $metaData = null
    ) {
    }
}
