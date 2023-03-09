<?php

namespace Neos\Eel\Tests\Unit\Utility\Fixtures;

class ExampleStaticFactoryFunction
{
    public static function exampleStaticFunction($argumentOne, $argumentTwo): string
    {
        return json_encode(['exampleStaticFunction' => [$argumentOne, $argumentTwo]]);
    }
}
