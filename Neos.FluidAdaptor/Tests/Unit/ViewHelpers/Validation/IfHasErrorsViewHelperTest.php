<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Security;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Validation\IfHasErrorsViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 */
final class IfHasErrorsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfHasErrorsViewHelper|MockObject
     */
    protected $viewHelper;

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(IfHasErrorsViewHelper::class, ['renderThenChild', 'renderElseChild']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function returnsAndRendersThenChildIfResultsHaveErrors()
    {
        $result = new Result();
        $result->addError(new Error('I am an error', 1386163707));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn(($result));
        $this->viewHelper->expects($this->once())->method('renderThenChild')->willReturn(('ThenChild'));
        self::assertEquals('ThenChild', $this->viewHelper->render());
    }

    #[Test]
    public function returnsAndRendersElseChildIfNoValidationResultsArePresentAtAll()
    {
        $this->viewHelper->expects($this->once())->method('renderElseChild')->willReturn(('ElseChild'));
        self::assertEquals('ElseChild', $this->viewHelper->render());
    }

    #[Test]
    public function queriesResultForPropertyIfPropertyPathIsGiven()
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->expects($this->once())->method('forProperty')->with('foo.bar.baz')->willReturn((new Result()));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn(($resultMock));

        $this->viewHelper->setArguments(['for' => 'foo.bar.baz']);
        $this->viewHelper->render();
    }
}
