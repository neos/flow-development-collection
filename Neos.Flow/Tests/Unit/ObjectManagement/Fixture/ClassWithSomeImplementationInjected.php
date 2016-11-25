<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

class ClassWithSomeImplementationInjected
{
    public $argument1;
    public $argument2;
    public $optionalSetterArgument;

    /**
     * The constructor
     *
     * @param SomeInterface $argument1
     * @param BasicClass $argument2
     */
    public function __construct(SomeInterface $argument1, BasicClass $argument2)
    {
        $this->argument1 = $argument1;
        $this->argument2 = $argument2;
    }

    /**
     * A setter for dependency injection
     *
     * @param SomeInterface $setterArgument
     * @return void
     */
    public function injectOptionalSetterArgument(SomeInterface $setterArgument)
    {
        $this->optionalSetterArgument = $setterArgument;
    }
}
