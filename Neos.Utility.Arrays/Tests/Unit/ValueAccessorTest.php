<?php

namespace Neos\Utility\Arrays\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;
use Neos\Utility\ValueAccessor;

/**
 * Testcase for the Utility Array class
 */
class ValueAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function intAccessorWorks()
    {
        $acceptableValues = [0, 1, -1, 99999, -99999];
        $inacceptableValues = [0.000001, 1.000001, true, false, 'string', new \DateTimeImmutable()];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'int');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'intOrNull');
    }

    /**
     * @test
     */
    public function floatAccessorWorks()
    {
        $acceptableValues = [0.000001, 1.000001];
        $inacceptableValues = [0, 1, 99999, -1, -99999, true, false, 'string', new \DateTimeImmutable()];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'float');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'floatOrNull');
    }

    /**
     * @test
     */
    public function numberAccessorWorks()
    {
        $acceptableValues = [0.000001, 1.000001, 0, 1, 99999, -1, -99999];
        $inacceptableValues = [true, false, 'string', new \DateTimeImmutable()];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'number');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'numberOrNull');
    }

    /**
     * @test
     */
    public function stringAccessorWorks()
    {
        $acceptableValues = ['string', ''];
        $inacceptableValues = [1, 0, -1, 9999, 0.000001, 1.000001, true, false, new \DateTimeImmutable()];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'string');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'stringOrNull');
    }

    /**
     * @test
     */
    public function arrayAccessorWorks()
    {
        $acceptableValues = [[], [1,2,3], ['foo'=>'bar']];
        $inacceptableValues = [0, 1, 99999, -1, -99999, true, false, 'string', new \DateTimeImmutable()];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'array');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'arrayOrNull');
    }

    /**
     * @test
     */
    public function classstringAccessorWorks()
    {
        $acceptableValues = [\DateTime::class, \DateTimeImmutable::class];
        $inacceptableValues = [\DateTimeInterface::class, 0, 1, false, true, 'string', '\This\Class\DoesNotExist'];

        $this->testAccessor($acceptableValues, [...$inacceptableValues, null], 'classString');
        $this->testAccessor([...$acceptableValues, null], $inacceptableValues, 'classStringOrNull');
    }

    /**
     * @test
     */
    public function instanceOfAccessorWorks()
    {
        $acceptableAsDateTimeInterface = [new \DateTime(), new \DateTimeImmutable()];
        $notAcceptableAsDateTimeInterface = [new \stdClass(), 1, -1, true, false, 'string'];

        $this->testAccessor($acceptableAsDateTimeInterface, [...$notAcceptableAsDateTimeInterface, null], 'object', [\DateTimeInterface::class]);
        $this->testAccessor([...$acceptableAsDateTimeInterface, null], $notAcceptableAsDateTimeInterface, 'objectOrNull', [\DateTimeInterface::class]);

        $acceptableAsDateTime = [new \DateTime()];
        $notAcceptableAsDateTime = [new \stdClass(), new \DateTimeImmutable(), 1, -1, true, false, 'string'];

        $this->testAccessor($acceptableAsDateTime, [...$notAcceptableAsDateTime, null], 'instanceOf', [\DateTime::class]);
        $this->testAccessor([...$acceptableAsDateTime, null], $notAcceptableAsDateTime, 'instanceOfOrNull', [\DateTime::class]);
    }

    protected function testAccessor(
        array  $acceptibleValues,
        array  $inacceptibleValues,
        string $methodName,
        array  $methodArguments = [],
    ): void {
        foreach ($acceptibleValues as $value) {
            $accessor = new ValueAccessor($value, (is_scalar($value) || $value instanceof \Stringable) ? (string)$value : get_debug_type($value) . 'was given');
            $result = $accessor->$methodName(...$methodArguments);
            $this->assertEquals($value, $result);
        }
        foreach ($inacceptibleValues as $value) {
            $accessor = new ValueAccessor($value, (is_scalar($value) || $value instanceof \Stringable) ? (string)$value : get_debug_type($value) . 'was given');
            try {
                $accessor->$methodName(...$methodArguments);
                $this->fail('this should lead to an error');
            } catch (\Error $error) {
                $this->assertInstanceOf(\TypeError::class, $error);
            }
        }
    }
}
