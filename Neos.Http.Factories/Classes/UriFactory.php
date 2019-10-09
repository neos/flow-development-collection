<?php
namespace Neos\Http\Factories;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 */
class UriFactory implements UriFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
