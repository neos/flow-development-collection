<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SampleMethodAttribute
{
    public function __construct(
        public readonly string $method,
        public readonly array $options = [],
        public readonly string $argWithDefault = 'default'
    ) {
    }
}
