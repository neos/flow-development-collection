<?php

declare(strict_types=1);

namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

/**
 *
 */
trait RequestFactoryTrait
{
    /**
     * @param non-empty-string $method
     * @inheritDoc
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
