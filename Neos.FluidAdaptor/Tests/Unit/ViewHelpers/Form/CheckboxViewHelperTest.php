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

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(CheckboxViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration', 'registerRenderMethodArguments']);
        $this->arguments['property'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setTagName', 'addAttribute'])->getMock();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects($this->any())->method('setTagName')->with('input');
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
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
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo[]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo[]');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(['foo', 'bar', 'baz']));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo[]');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \ArrayObject(['foo', 'bar', 'baz'])));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAnEntityCollection()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', '1');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $user_kd = new UserDomainClass(1, 'Karsten', 'Dambekalns');
        $user_bw = new UserDomainClass(2, 'Bastian', 'Waidelich');

        $userCollection = new ArrayCollection([$user_kd, $user_bw]);

        /** @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockPersistenceManager */
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->willReturnCallback(function (UserDomainClass $user) {
            return (string)$user->getId();
        });
        $this->viewHelper->injectPersistenceManager($mockPersistenceManager);

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('1'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue($userCollection));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'checkbox');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', '');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \stdClass()));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }
}
