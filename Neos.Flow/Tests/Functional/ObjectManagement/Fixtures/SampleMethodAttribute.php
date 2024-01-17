<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SampleMethodAttribute
{
    public function __construct(
        readonly public string $method,
        readonly public array $options = [],
        readonly public string $argWithDefault = 'default'
    ) {
    }
}
