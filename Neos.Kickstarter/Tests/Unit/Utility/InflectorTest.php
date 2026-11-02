<?php

declare(strict_types=1);

namespace Neos\Kickstarter\Tests\Unit\Utility;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Kickstarter\Utility\Inflector;
use PHPUnit\Framework\Attributes\Test;

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
final class InflectorTest extends UnitTestCase
{
    #[Test]
    public function humanizeCamelCaseConvertsCamelCaseToSpacesAndUppercasesFirstWord()
    {
        $inflector = new Inflector();
        $humanized = $inflector->humanizeCamelCase('BlogAuthor');
        self::assertEquals('Blog author', $humanized);
    }

    #[Test]
    public function pluralizePluralizesWords()
    {
        $inflector = new Inflector();
        self::assertEquals('boxes', $inflector->pluralize('box'));
        self::assertEquals('foos', $inflector->pluralize('foo'));
    }
}
