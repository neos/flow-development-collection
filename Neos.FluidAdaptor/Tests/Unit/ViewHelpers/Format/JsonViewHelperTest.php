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

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper
 */
class JsonViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperConvertsSimpleAssociativeArrayGivenAsChildren()
    {
        $this->viewHelper
                ->expects($this->once())
                ->method('renderChildren')
                ->will($this->returnValue(array('foo' => 'bar')));

        $actualResult = $this->viewHelper->render();
        $this->assertEquals('{"foo":"bar"}', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsSimpleAssociativeArrayGivenAsDataArgument()
    {
        $this->viewHelper
                ->expects($this->never())
                ->method('renderChildren');

        $actualResult = $this->viewHelper->render(array('foo' => 'bar'));
        $this->assertEquals('{"foo":"bar"}', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperOutputsArrayOnIndexedArrayInputAndObjectIfSetSo()
    {
        $this->viewHelper
                ->expects($this->any())
                ->method('renderChildren')
                ->will($this->returnValue(array('foo', 'bar', 42)));

        $this->assertEquals('["foo","bar",42]', $this->viewHelper->render());
        $this->assertEquals('{"0":"foo","1":"bar","2":42}', $this->viewHelper->render(null, true));
    }

    /**
     * @test
     */
    public function viewHelperEscapesGreaterThanLowerThanCharacters()
    {
        $this->assertEquals('["\u003Cfoo\u003E","bar","elephant \u003E mouse"]', $this->viewHelper->render(array('<foo>', 'bar', 'elephant > mouse')));
        $this->assertEquals('{"0":"\u003Cfoo\u003E","1":"bar","2":"elephant \u003E mouse"}', $this->viewHelper->render(array('<foo>', 'bar', 'elephant > mouse'), true));
    }
}
