<?php
namespace Neos\Kickstarter\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Kickstarter package.
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
class InflectorTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function humanizeCamelCaseConvertsCamelCaseToSpacesAndUppercasesFirstWord()
    {
        $inflector = new \Neos\Kickstarter\Utility\Inflector();
        $humanized = $inflector->humanizeCamelCase('BlogAuthor');
        self::assertEquals('Blog author', $humanized);
    }

    /**
     * @test
     */
    public function pluralizePluralizesWords()
    {
        $inflector = new \Neos\Kickstarter\Utility\Inflector();
        self::assertEquals('boxes', $inflector->pluralize('box'));
        self::assertEquals('foos', $inflector->pluralize('foo'));
    }
}
