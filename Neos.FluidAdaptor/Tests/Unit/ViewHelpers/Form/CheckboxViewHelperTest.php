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

use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserDomainClass;
use Neos\FluidAdaptor\ViewHelpers\Form\CheckboxViewHelper;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Checkbox" Form view helper
 */
class CheckboxViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
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

        $this->mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setTagName', 'addAttribute'])->getMock();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $this->mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $this->mockTagBuilder->expects(self::exactly(7))->method('addAttribute')->withConsecutive(
            // first invocation below
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', ''],
            // second invocation below
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => false]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo[]'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo[]'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(['foo', 'bar', 'baz']));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo[]'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(new \ArrayObject(['foo', 'bar', 'baz'])));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAnEntityCollection()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', '1'],
            ['checked', '']
        );

        $user_kd = new UserDomainClass(1, 'Karsten', 'Dambekalns');
        $user_bw = new UserDomainClass(2, 'Bastian', 'Waidelich');

        $userCollection = new ArrayCollection([$user_kd, $user_bw]);

        /** @var PersistenceManagerInterface|\PHPUnit\Framework\MockObject\MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturnCallback(function (UserDomainClass $user) {
            return (string)$user->getId();
        });
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('1'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue($userCollection));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'checkbox'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(new \stdClass()));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }
}
