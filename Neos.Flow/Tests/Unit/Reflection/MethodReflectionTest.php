<?php
namespace Neos\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Reflection;
use Neos\Flow\Tests\Unit\Reflection\Fixture\ClassWithAnnotatedMethod;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for MethodReflection
 */
class MethodReflectionTest extends UnitTestCase
{
    /**
     * @var mixed
     */
    protected $someProperty;

    /**
     * @test
     */
    public function getDeclaringClassReturnsFlowsClassReflection()
    {
        $method = new Reflection\MethodReflection(__CLASS__, __FUNCTION__);
        self::assertInstanceOf(Reflection\ClassReflection::class, $method->getDeclaringClass());
    }

    /**
     * @test
     */
    public function getParametersReturnsFlowsParameterReflection($dummyArg1 = null, $dummyArg2 = null)
    {
        $method = new Reflection\MethodReflection(__CLASS__, __FUNCTION__);
        foreach ($method->getParameters() as $parameter) {
            self::assertInstanceOf(Reflection\ParameterReflection::class, $parameter);
            self::assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
        }
    }

    public function classAndMethodWithAnnotations()
    {
        return [
            [ClassWithAnnotatedMethod::class, 'methodWithTag', ['skipcsrfprotection' => []]],
            [ClassWithAnnotatedMethod::class, 'methodWithTagAndComment', ['skipcsrfprotection' => ['Some comment']]],
            [ClassWithAnnotatedMethod::class, 'methodWithAnnotation', ['flow\skipcsrfprotection' => []]],
            [ClassWithAnnotatedMethod::class, 'methodWithAnnotationAndComment', ['flow\skipcsrfprotection' => ['Some comment']]],
            [ClassWithAnnotatedMethod::class, 'methodWithAnnotationArgument', ['flow\validate' => ['"foo"']]],
            [ClassWithAnnotatedMethod::class, 'methodWithAnnotationArgumentAndComment', ['flow\validate' => ['"foo"']]],
            [ClassWithAnnotatedMethod::class, 'methodWithMultipleAnnotationArguments', ['flow\ignorevalidation' => ['argumentName="foo", evaluate=true']]],
            [ClassWithAnnotatedMethod::class, 'methodWithMultipleAnnotationArgumentsAndComment', ['flow\ignorevalidation' => ['argumentName="foo", evaluate=true']]],
        ];
    }

    /**
     * @test
     * @dataProvider classAndMethodWithAnnotations
     */
    public function commentsAfterAnnotationShouldBeIgnored($class, $method, $expected)
    {
        $method = new Reflection\MethodReflection($class, $method);
        self::assertEquals($method->getTagsValues());
    }
}
