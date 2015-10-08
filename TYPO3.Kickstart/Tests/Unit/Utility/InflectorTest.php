<?php
namespace TYPO3\Kickstart\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once(__DIR__ . '/../../../Resources/Private/PHP/Sho_Inflect.php');

/**
 * Testcase for the Inflector
 *
 */
class InflectorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function humanizeCamelCaseConvertsCamelCaseToSpacesAndUppercasesFirstWord()
    {
        $inflector = new \TYPO3\Kickstart\Utility\Inflector();
        $humanized = $inflector->humanizeCamelCase('BlogAuthor');
        $this->assertEquals('Blog author', $humanized);
    }

    /**
     * @test
     */
    public function pluralizePluralizesWords()
    {
        $inflector = new \TYPO3\Kickstart\Utility\Inflector();
        $this->assertEquals('boxes', $inflector->pluralize('box'));
        $this->assertEquals('foos', $inflector->pluralize('foo'));
    }
}
