<?php

namespace Neos\Eel\Tests\Functional\Utility\Fixtures;

use Neos\Eel\ProtectedContextAwareInterface;

class ExampleProtectedContextAwareHelper implements ProtectedContextAwareInterface
{
    public function exampleFunction($argumentOne, $argumentTwo): string
    {
        return json_encode(['ExampleProtectedContextAwareHelper::exampleFunction' => [$argumentOne, $argumentTwo]]);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
