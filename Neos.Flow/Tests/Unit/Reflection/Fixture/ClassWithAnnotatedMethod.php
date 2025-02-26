<?php

namespace Neos\Flow\Tests\Unit\Reflection\Fixture;

use Neos\Flow\Annotations as Flow;

class ClassWithAnnotatedMethod
{
    /**
     * @skipcsrfprotection
     */
    public function methodWithTag()
    {
    }

    /**
     * @skipcsrfprotection Some comment
     */
    public function methodWithTagAndComment()
    {
    }

    /**
     * @Flow\SkipCsrfProtection
     */
    public function methodWithAnnotation()
    {
    }

    /**
     * @Flow\SkipCsrfProtection Some comment
     */
    public function methodWithAnnotationAndComment()
    {
    }

    /**
     * @Flow\Validate("foo")
     */
    public function methodWithAnnotationArgument(string $foo)
    {
    }

    /**
     * @Flow\Validate("foo") Some comment
     */
    public function methodWithAnnotationArgumentAndComment(string $foo)
    {
    }

    /**
     * @Flow\IgnoreValidation(argumentName="foo", evaluate=true)
     */
    public function methodWithMultipleAnnotationArguments(string $foo)
    {
    }

    /**
     * @Flow\IgnoreValidation(argumentName="foo", evaluate=true) Some comment
     */
    public function methodWithMultipleAnnotationArgumentsAndComment(string $foo)
    {
    }
}
