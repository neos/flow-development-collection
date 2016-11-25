<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

class ClassWithUnmatchedRequiredSetterDependency
{
    public $requiredSetterArgument;

    public function injectRequiredSetterArgument(\stdClass $setterArgument)
    {
        $this->requiredSetterArgument = $setterArgument;
    }
}
