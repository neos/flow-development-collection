<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SampleAttribute
{
    public string $class;
    public array $options;
    public string $argWithDefault;
    public function __construct(string $class, array $options = [], string $argWithDefault = 'default')
    {
        $this->class = $class;
        $this->options = $options;
        $this->argWithDefault = $argWithDefault;
    }
}
