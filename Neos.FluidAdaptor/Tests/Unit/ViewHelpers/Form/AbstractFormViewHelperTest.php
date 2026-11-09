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
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the Abstract Form view helper
 *
 */
final class AbstractFormViewHelperTest extends ViewHelperBaseTestcase
{
    #[Test]
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUUID()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createStub($fullClassName);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn(('123'));

        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->method('prefixFieldName')->with('theName')->willReturn(('prefix[theName]'));
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createStub($fullClassName);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn(('123'));

        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->method('prefixFieldName')->with('theName')->willReturn(('prefix[theName]'));
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function renderHiddenIdentityFieldReturnsACommentIfTheObjectIsWithoutIdentity()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createStub($fullClassName);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->willReturn((null));

        $expectedResult = chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL()
    {
        $viewHelper = $this->getAccessibleMock(AbstractFormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        self::assertSame('', $viewHelper->_call('prefixFieldName', null));
    }

    #[Test]
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(AbstractFormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        self::assertSame('', $viewHelper->_call('prefixFieldName', ''));
    }

    #[Test]
    public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(AbstractFormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'fieldNamePrefix' => '',
            ]
        ];

        self::assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    #[Test]
    public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix()
    {
        $viewHelper = $this->getAccessibleMock(AbstractFormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'fieldNamePrefix' => 'somePrefix',
            ]
        ];

        self::assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    #[Test]
    public function prefixFieldNamePreservesSquareBracketsOfFieldName()
    {
        $viewHelper = $this->getAccessibleMock(AbstractFormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'fieldNamePrefix' => 'somePrefix[foo]',
            ]
        ];

        self::assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
    }
}
