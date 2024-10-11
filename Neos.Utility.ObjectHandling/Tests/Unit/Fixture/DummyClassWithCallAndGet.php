<?php

declare(strict_types=1);

namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture;

class DummyClassWithCallAndGet
{
    public function __call($name, $arguments)
    {
        throw new \RuntimeException('should not be invoked');
    }

    public function __get($name)
    {
        return sprintf('__get %s', $name);
    }
}
