<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Hidden" Form view helper
 */
class HiddenViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\HiddenViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\HiddenViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setTagName', 'addAttribute'])->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'hidden'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::once())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::once())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
