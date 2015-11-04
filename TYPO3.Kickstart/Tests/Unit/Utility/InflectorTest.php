<?php
namespace TYPO3\Kickstart\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3.Kickstart package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
