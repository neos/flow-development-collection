<?php

declare(strict_types=1);

namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture;

class DummyClassWithCall
{
    public function __call($name, $arguments)
    {
        return sprintf('__call %s %s', $name, json_encode($arguments));
    }
}
