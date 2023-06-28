<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;

/**
 * A class to test static compile functionality
 */
class PrototypeClassK
{
    public function getMicrotime(): float
    {
        return static::compiledStaticallyMethod();
    }

    /**
     * Method that should get static compiled into the proxy, saving some processing power in production,
     * but also providing the exact same result on every call.
     *
     * @return string
     * @Flow\CompileStatic
     */
    public static function compiledStaticallyMethod(): string
    {
        return Algorithms::generateRandomToken(10);
    }
}
