<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

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
