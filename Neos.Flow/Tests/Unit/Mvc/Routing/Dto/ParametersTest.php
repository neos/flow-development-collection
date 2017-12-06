<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Mvc\Routing\Dto\Parameters;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Parameters DTO
 */
class ParametersTest extends UnitTestCase
{
    public function withParameterThrowsExceptionForInvalidParameterValuesDataProvider()
    {
        return [
            ['parameterValue' => new \stdClass()],
            ['parameterValue' => $this->getMockBuilder(RouterInterface::class)->getMock()],
            ['parameterValue' => null],
        ];
    }

    /**
     * @test
     * @dataProvider withParameterThrowsExceptionForInvalidParameterValuesDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function withParameterThrowsExceptionForInvalidParameterValues($parameterValue)
    {
        Parameters::createEmpty()->withParameter('someParameter', $parameterValue);
    }

    public function withParameterAcceptsValidParameterValuesDataProvider()
    {
        return [
            ['parameterValue' => 'string'],
            ['parameterValue' => 123],
            ['parameterValue' => 123.45],
            ['parameterValue' => true],
            ['parameterValue' => false],
            ['parameterValue' => $this->getMockBuilder(CacheAwareInterface::class)->getMock()],
        ];
    }

    /**
     * @test
     * @dataProvider withParameterAcceptsValidParameterValuesDataProvider
     */
    public function withParameterAcceptsValidParameterValues($parameterValue)
    {
        Parameters::createEmpty()->withParameter('someParameter', $parameterValue);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function withParameterDoesNotMutateTheObject()
    {
        $originalParameters = Parameters::createEmpty();
        $originalParameters->withParameter('someParameter', 'someValue');
        $this->assertFalse($originalParameters->has('someParameter'));
    }

    /**
     * @test
     */
    public function withParameterReturnsANewInstanceWithTheGivenParameter()
    {
        $originalParameters = Parameters::createEmpty()->withParameter('someParameter', 'someValue');
        $this->assertSame('someValue', $originalParameters->getValue('someParameter'));
    }

    /**
     * @test
     */
    public function withParameterOverridesAnyPreviousParameters()
    {
        $originalParameters = Parameters::createEmpty()->withParameter('someParameter', 'someValue');
        $originalParameters = $originalParameters->withParameter('someParameter', 'overriddenValue');
        $this->assertSame('overriddenValue', $originalParameters->getValue('someParameter'));
    }
}
