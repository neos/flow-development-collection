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
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper::class)->setMethods(['renderChildren', 'registerRenderMethodArguments'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperConvertsSimpleAssociativeArrayGivenAsChildren()
    {
        $this->viewHelper
                ->expects($this->once())
                ->method('renderChildren')
                ->will($this->returnValue(['foo' => 'bar']));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
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

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['foo' => 'bar']]);
        $actualResult = $this->viewHelper->render();
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
                ->will($this->returnValue(['foo', 'bar', 42]));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->assertEquals('["foo","bar",42]', $this->viewHelper->render());

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => null, 'forceObject' => true]);
        $this->assertEquals('{"0":"foo","1":"bar","2":42}', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function viewHelperEscapesGreaterThanLowerThanCharacters()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['<foo>', 'bar', 'elephant > mouse']]);
        $this->assertEquals('["\u003Cfoo\u003E","bar","elephant \u003E mouse"]', $this->viewHelper->render());
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['<foo>', 'bar', 'elephant > mouse'], 'forceObject' => true]);
        $this->assertEquals('{"0":"\u003Cfoo\u003E","1":"bar","2":"elephant \u003E mouse"}', $this->viewHelper->render());
    }
}
