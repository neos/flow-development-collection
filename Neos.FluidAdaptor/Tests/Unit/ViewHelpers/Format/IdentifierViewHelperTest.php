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

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\IdentifierViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\IdentifierViewHelper
 */
final class IdentifierViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\IdentifierViewHelper
     */
    protected $viewHelper;

    /**
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(IdentifierViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->viewHelper->_set('persistenceManager', $this->mockPersistenceManager);
    }

    #[Test]
    public function renderGetsIdentifierForObjectFromPersistenceManager()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager
            ->expects($this->atLeastOnce())
            ->method('getIdentifierByObject')
            ->with($object)
            ->willReturn(('6f487e40-4483-11de-8a39-0800200c9a66'));

        $expectedResult = '6f487e40-4483-11de-8a39-0800200c9a66';

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $object]);
        $actualResult = $this->viewHelper->render();

        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderWithoutValueInvokesRenderChildren()
    {
        $object = new \stdClass();
        $this->viewHelper
            ->expects($this->once())
            ->method('renderChildren')
            ->willReturn(($object));

        $this->mockPersistenceManager
            ->expects($this->once())
            ->method('getIdentifierByObject')
            ->with($object)
            ->willReturn(('b59292c5-1a28-4b36-8615-10d3c5b3a4d8'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertEquals('b59292c5-1a28-4b36-8615-10d3c5b3a4d8', $this->viewHelper->render());
    }

    #[Test]
    public function renderReturnsNullIfGivenValueIsNull()
    {
        $this->viewHelper
            ->expects($this->once())
            ->method('renderChildren')
            ->willReturn((null));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertEquals(null, $this->viewHelper->render());
    }

    #[Test]
    public function renderThrowsExceptionIfGivenValueIsNoObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $notAnObject = [];
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $notAnObject]);
        $this->viewHelper->render();
    }
}
