<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\RequestFactoryInterface;

/**
 *
 */
class RequestFactory implements RequestFactoryInterface
{
    use RequestFactoryTrait;
}
