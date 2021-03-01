<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SampleAttribute
{
    public string $class;
    public function __construct(string $class)
    {
        $this->class = $class;
    }
}
