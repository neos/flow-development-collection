<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

class ClassImplementingInterfaceWithConstructor implements InterfaceWithConstructor
{
    public function __construct(string $foo)
    {
    }
}
