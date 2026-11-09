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

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper
 */
final class JsonViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\JsonViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(JsonViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function viewHelperConvertsSimpleAssociativeArrayGivenAsChildren()
    {
        $this->viewHelper
                ->expects($this->once())
                ->method('renderChildren')
                ->willReturn((['foo' => 'bar']));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('{"foo":"bar"}', $actualResult);
    }

    #[Test]
    public function viewHelperConvertsSimpleAssociativeArrayGivenAsDataArgument()
    {
        $this->viewHelper
                ->expects($this->never())
                ->method('renderChildren');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['foo' => 'bar']]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('{"foo":"bar"}', $actualResult);
    }

    #[Test]
    public function viewHelperOutputsArrayOnIndexedArrayInputAndObjectIfSetSo()
    {
        $this->viewHelper
                ->method('renderChildren')
                ->willReturn((['foo', 'bar', 42]));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertEquals('["foo","bar",42]', $this->viewHelper->render());

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => null, 'forceObject' => true]);
        self::assertEquals('{"0":"foo","1":"bar","2":42}', $this->viewHelper->render());
    }

    #[Test]
    public function viewHelperEscapesGreaterThanLowerThanCharacters()
    {
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['<foo>', 'bar', 'elephant > mouse']]);
        self::assertEquals('["\u003Cfoo\u003E","bar","elephant \u003E mouse"]', $this->viewHelper->render());
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => ['<foo>', 'bar', 'elephant > mouse'], 'forceObject' => true]);
        self::assertEquals('{"0":"\u003Cfoo\u003E","1":"bar","2":"elephant \u003E mouse"}', $this->viewHelper->render());
    }
}
