<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\StreamFactoryInterface;

/**
 *
 */
class StreamFactory implements StreamFactoryInterface
{
    use StreamFactoryTrait;
}
