<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * A factory that implements all interfaces of PSR 17
 *
 * This factory can be used to simply create Requests, Uris and Streams without having to inject the traits yourself.
 */
class PsrHttpFactory implements ServerRequestFactoryInterface, RequestFactoryInterface, ResponseFactoryInterface,
                                UriFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface
{
    use ServerRequestFactoryTrait;
    use RequestFactoryTrait;
    use ResponseFactoryTrait;
    use UriFactoryTrait;
    use StreamFactoryTrait;
    use UploadedFileFactoryTrait;
}
