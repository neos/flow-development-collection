<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for \Neos\FluidAdaptor\Core\ViewHelper\ArgumentDefinition
 */
class ArgumentDefinitionTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function objectStoresDataCorrectly()
    {
        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $isMethodParameter = true;
        $argumentDefinition = new \Neos\FluidAdaptor\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired, null, $isMethodParameter);

        $this->assertEquals($argumentDefinition->getName(), $name, 'Name could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->getDescription(), $description, 'Description could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->getType(), $type, 'Type could not be retrieved correctly');
        $this->assertEquals($argumentDefinition->isRequired(), $isRequired, 'Required flag could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->isMethodParameter(), $isMethodParameter, 'isMethodParameter flag could not be retrieved correctly.');
    }
}
