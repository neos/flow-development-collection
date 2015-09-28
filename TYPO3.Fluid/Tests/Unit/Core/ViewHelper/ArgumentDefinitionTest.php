<?php
namespace TYPO3\Fluid\Tests\Unit\Core\ViewHelper;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition
 */
class ArgumentDefinitionTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $argumentDefinition = new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired, null, $isMethodParameter);

        $this->assertEquals($argumentDefinition->getName(), $name, 'Name could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->getDescription(), $description, 'Description could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->getType(), $type, 'Type could not be retrieved correctly');
        $this->assertEquals($argumentDefinition->isRequired(), $isRequired, 'Required flag could not be retrieved correctly.');
        $this->assertEquals($argumentDefinition->isMethodParameter(), $isMethodParameter, 'isMethodParameter flag could not be retrieved correctly.');
    }
}
