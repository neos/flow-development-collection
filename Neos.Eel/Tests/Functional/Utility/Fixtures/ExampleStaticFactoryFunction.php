<?php

namespace Neos\Eel\Tests\Functional\Utility\Fixtures;

class ExampleStaticFactoryFunction
{
    public static function exampleStaticFunction($argumentOne, $argumentTwo): string
    {
        return json_encode(['exampleStaticFunction' => [$argumentOne, $argumentTwo]]);
    }
}