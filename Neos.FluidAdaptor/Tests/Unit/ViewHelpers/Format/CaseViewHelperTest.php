<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper
 */
final class CaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper
     */
    protected $viewHelper;

    /**
     * Holds the initial mb_internal_encoding value found on this system in order to restore it after the tests
     * @var string
     */
    protected $originalMbEncodingValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderingContext = $this->createMock(RenderingContext::class);
        $this->viewHelper = $this->getAccessibleMock(CaseViewHelper::class, []);
        $this->viewHelper->setRenderingContext($this->renderingContext);
        $this->originalMbEncodingValue = mb_internal_encoding();
    }

    /**
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        mb_internal_encoding($this->originalMbEncodingValue);
    }

    #[Test]
    public function viewHelperRendersChildrenIfGivenValueIsNull()
    {
        $testString = 'child was here';
        $this->viewHelper->setRenderChildrenClosure(function () use ($testString) {
            return $testString;
        });
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $result = $this->viewHelper->render();
        self::assertEquals(strtoupper($testString), $result);
    }

    /**
     *
     */
    public static function fixtureStringDataProvider(): \Iterator
    {
        yield ['', ''];
        yield [0, '0'];
        yield ['foo', 'FOO'];
    }

    #[DataProvider('fixtureStringDataProvider')]
    #[Test]
    public function viewHelperDoesNotRenderChildrenIfGivenValueIsNotNull($testString, $expected)
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $testString]);
        $result = $this->viewHelper->render();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven()
    {
        $this->expectException(InvalidVariableException::class);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'Foo', 'mode' => 'incorrectMode']);
        $this->viewHelper->render('Foo', 'incorrectMode');
    }

    #[Test]
    public function viewHelperRestoresMbInternalEncodingValueAfterInvocation()
    {
        mb_internal_encoding('ASCII');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'dummy']);
        $this->viewHelper->render();
        self::assertSame('ASCII', mb_internal_encoding());
    }

    #[Test]
    public function viewHelperRestoresMbInternalEncodingAfterExceptionOccurred()
    {
        $this->expectException(InvalidVariableException::class);
        mb_internal_encoding('ASCII');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'dummy', 'mode' => 'incorrectModeResultingInException']);
        $this->viewHelper->render();
        self::assertSame('ASCII', mb_internal_encoding());
    }

    #[Test]
    public function viewHelperConvertsUppercasePerDefault()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'FooB4r']);
        self::assertSame('FOOB4R', $this->viewHelper->render());
    }

    /**
     * Signature: $input, $mode, $expected
     */
    public static function conversionTestingDataProvider(): \Iterator
    {
        yield ['FooB4r', CaseViewHelper::CASE_LOWER, 'foob4r'];
        yield ['FooB4r', CaseViewHelper::CASE_UPPER, 'FOOB4R'];
        yield ['foo bar', CaseViewHelper::CASE_CAPITAL, 'Foo bar'];
        yield ['FOO Bar', CaseViewHelper::CASE_UNCAPITAL, 'fOO Bar'];
        yield ['fOo bar BAZ', CaseViewHelper::CASE_CAPITAL_WORDS, 'Foo Bar Baz'];
        yield ['smørrebrød', CaseViewHelper::CASE_UPPER, 'SMØRREBRØD'];
        yield ['smørrebrød', CaseViewHelper::CASE_CAPITAL, 'Smørrebrød'];
        yield ['römtömtömtöm', CaseViewHelper::CASE_UPPER, 'RÖMTÖMTÖMTÖM'];
        yield ['smörrebröd smörrebröd RÖMTÖMTÖMTÖM', CaseViewHelper::CASE_CAPITAL_WORDS, 'Smörrebröd Smörrebröd Römtömtömtöm'];
        yield ['Ἕλλάς α ω', CaseViewHelper::CASE_UPPER, 'ἝΛΛΆΣ Α Ω'];
    }

    #[DataProvider('conversionTestingDataProvider')]
    #[Test]
    public function viewHelperConvertsCorrectly($input, $mode, $expected)
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $input, 'mode' => $mode]);
        self::assertSame($expected, $this->viewHelper->render(), sprintf('The conversion with mode "%s" did not perform as expected.', $mode));
    }
}
