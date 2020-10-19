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

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the Abstract Form view helper
 *
 */
class AbstractFormViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUUID()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createMock($fullClassName);

        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue('123'));

        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->expects(self::any())->method('prefixFieldName')->with('theName')->will(self::returnValue('prefix[theName]'));
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createMock($fullClassName);

        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue('123'));

        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->expects(self::any())->method('prefixFieldName')->with('theName')->will(self::returnValue('prefix[theName]'));
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsACommentIfTheObjectIsWithoutIdentity()
    {
        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createMock($fullClassName);

        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue(null));

        $expectedResult = chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        self::assertSame('', $viewHelper->_call('prefixFieldName', null));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        self::assertSame('', $viewHelper->_call('prefixFieldName', ''));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'fieldNamePrefix' => '',
            ]
        ];

        self::assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'fieldNamePrefix' => 'somePrefix',
            ]
        ];

        self::assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePreservesSquareBracketsOfFieldName()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'fieldNamePrefix' => 'somePrefix[foo]',
            ]
        ];

        self::assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
    }
}
