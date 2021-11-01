<?php

namespace Neos\Flow\Tests\Unit\Reflection\Fixture;

use Neos\Flow\Annotations as Flow;

class ClassWithAnnotatedMethod
{
    /**
     * @Flow\SkipCsrfProtection Some comment
     */
    public function methodWithComment() {}

    /**
     * @Flow\SkipCsrfProtection
     */
    public function methodWithoutComment() {}
}