<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 *
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    use ServerRequestFactoryTrait;
}
