<?php
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

use Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper
 */
class CaseViewHelperTest extends ViewHelperBaseTestcase
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

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\CaseViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $this->originalMbEncodingValue = mb_internal_encoding();
    }

    /**
     */
    protected function tearDown()
    {
        parent::tearDown();
        mb_internal_encoding($this->originalMbEncodingValue);
    }

    /**
     * @test
     */
    public function viewHelperRendersChildrenIfGivenValueIsNull()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function viewHelperDoesNotRenderChildrenIfGivenValueIsNotNull()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper->render('');
        $this->viewHelper->render(0);
        $this->viewHelper->render('foo');
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven()
    {
        $this->viewHelper->render('Foo', 'incorrectMode');
    }

    /**
     * @test
     */
    public function viewHelperRestoresMbInternalEncodingValueAfterInvocation()
    {
        mb_internal_encoding('ASCII');
        $this->viewHelper->render('dummy');
        $this->assertEquals('ASCII', mb_internal_encoding());
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException
     */
    public function viewHelperRestoresMbInternalEncodingAfterExceptionOccurred()
    {
        mb_internal_encoding('ASCII');
        $this->viewHelper->render('dummy', 'incorrectModeResultingInException');
        $this->assertEquals('ASCII', mb_internal_encoding());
    }

    /**
     * @test
     */
    public function viewHelperConvertsUppercasePerDefault()
    {
        $this->assertSame('FOOB4R', $this->viewHelper->render('FooB4r'));
    }

    /**
     * Signature: $input, $mode, $expected
     */
    public function conversionTestingDataProvider()
    {
        return array(
            array('FooB4r', CaseViewHelper::CASE_LOWER, 'foob4r'),
            array('FooB4r', CaseViewHelper::CASE_UPPER, 'FOOB4R'),
            array('foo bar', CaseViewHelper::CASE_CAPITAL, 'Foo bar'),
            array('FOO Bar', CaseViewHelper::CASE_UNCAPITAL, 'fOO Bar'),
            array('fOo bar BAZ', CaseViewHelper::CASE_CAPITAL_WORDS, 'Foo Bar Baz'),
            array('smørrebrød', CaseViewHelper::CASE_UPPER, 'SMØRREBRØD'),
            array('smørrebrød', CaseViewHelper::CASE_CAPITAL, 'Smørrebrød'),
            array('römtömtömtöm', CaseViewHelper::CASE_UPPER, 'RÖMTÖMTÖMTÖM'),
            array('smörrebröd smörrebröd RÖMTÖMTÖMTÖM', CaseViewHelper::CASE_CAPITAL_WORDS, 'Smörrebröd Smörrebröd Römtömtömtöm'),
            array('Ἕλλάς α ω', CaseViewHelper::CASE_UPPER, 'ἝΛΛΆΣ Α Ω'),
        );
    }

    /**
     * @test
     * @dataProvider conversionTestingDataProvider
     */
    public function viewHelperConvertsCorrectly($input, $mode, $expected)
    {
        $this->assertSame($expected, $this->viewHelper->render($input, $mode), sprintf('The conversion with mode "%s" did not perform as expected.', $mode));
    }
}
