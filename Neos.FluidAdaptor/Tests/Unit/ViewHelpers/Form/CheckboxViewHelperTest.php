<?php

declare(strict_types=1);

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
use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass;
use Neos\FluidAdaptor\ViewHelpers\Form\CheckboxViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Checkbox" Form view helper
 */
final class CheckboxViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CheckboxViewHelper|MockObject
     */
    protected $viewHelper;

    /**
     * @var TagBuilder|MockObject
     */
    protected $mockTagBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(CheckboxViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['property'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setTagName', 'addAttribute'])->getMock();
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');
        $matcher = self::exactly(3);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $matcher = self::exactly(7);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 5) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 6) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 7) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => false]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $matcher = self::exactly(3);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo[]', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn(([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo[]', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((['foo', 'bar', 'baz']));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo[]', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((new \ArrayObject(['foo', 'bar', 'baz'])));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAnEntityCollection()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('1', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $user_kd = new UserDomainClass(1, 'Karsten', 'Dambekalns');
        $user_bw = new UserDomainClass(2, 'Bastian', 'Waidelich');

        $userCollection = new ArrayCollection([$user_kd, $user_bw]);

        /** @var PersistenceManagerInterface|MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('getIdentifierByObject')->willReturnCallback(function (UserDomainClass $user) {
            return (string)$user->getId();
        });
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('1'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn(($userCollection));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('checkbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((new \stdClass()));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }
}
