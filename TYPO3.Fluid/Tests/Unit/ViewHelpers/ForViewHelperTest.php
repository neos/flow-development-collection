<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

include_once(__DIR__ . '/Fixtures/ConstraintSyntaxTreeNode.php');
require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for ForViewHelper
 */
class ForViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    public function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array());
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);

        $this->arguments['reverse'] = null;
        $this->arguments['key'] = '';
        $this->arguments['iteration'] = null;
    }

    /**
     * @test
     */
    public function renderExecutesTheLoopCorrectly()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);
        $this->arguments['each'] = array(0, 1, 2, 3);
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);

        $expectedCallProtocol = array(
            array('innerVariable' => 0),
            array('innerVariable' => 1),
            array('innerVariable' => 2),
            array('innerVariable' => 3)
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeys()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array('key1' => 'value1', 'key2' => 'value2');
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            ),
            array(
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            )
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsNull()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $this->arguments['each'] = null;
        $this->arguments['as'] = 'foo';

        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsEmptyArray()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $this->arguments['each'] = array();
        $this->arguments['as'] = 'foo';

        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
    }

    /**
     * @test
     */
    public function renderIteratesElementsInReverseOrderIfReverseIsTrue()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array(0, 1, 2, 3);
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = array(
            array('innerVariable' => 3),
            array('innerVariable' => 2),
            array('innerVariable' => 1),
            array('innerVariable' => 0)
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeysIfReverseIsTrue()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array('key1' => 'value1', 'key2' => 'value2');
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            ),
            array(
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            )
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsNumericalIndexIfTheGivenArrayDoesNotHaveAKey()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array('foo', 'bar', 'baz');
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'foo',
                'someKey' => 0
            ),
            array(
                'innerVariable' => 'bar',
                'someKey' => 1
            ),
            array(
                'innerVariable' => 'baz',
                'someKey' => 2
            )
        );
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsNumericalIndexInAscendingOrderEvenIfReverseIsTrueIfTheGivenArrayDoesNotHaveAKey()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array('foo', 'bar', 'baz');
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'baz',
                'someKey' => 0
            ),
            array(
                'innerVariable' => 'bar',
                'someKey' => 1
            ),
            array(
                'innerVariable' => 'foo',
                'someKey' => 2
            )
        );
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();
        $object = new \stdClass();

        $this->arguments['each'] = $object;
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);
    }


    /**
     * @test
     */
    public function renderIteratesThroughElementsOfTraversableObjects()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = new \ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);

        $expectedCallProtocol = array(
            array('innerVariable' => 'value1'),
            array('innerVariable' => 'value2')
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeyWhenIteratingThroughElementsOfObjectsThatImplementIteratorInterface()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = new \ArrayIterator(array('key1' => 'value1', 'key2' => 'value2'));
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            ),
            array(
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            )
        );
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsTheNumericalIndexWhenIteratingThroughElementsOfObjectsOfTyeSplObjectStorage()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $splObjectStorageObject = new \SplObjectStorage();
        $object1 = new \stdClass();
        $splObjectStorageObject->attach($object1);
        $object2 = new \stdClass();
        $splObjectStorageObject->attach($object2, 'foo');
        $object3 = new \stdClass();
        $splObjectStorageObject->offsetSet($object3, 'bar');

        $this->arguments['each'] = $splObjectStorageObject;
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => $object1,
                'someKey' => 0
            ),
            array(
                'innerVariable' => $object2,
                'someKey' => 1
            ),
            array(
                'innerVariable' => $object3,
                'someKey' => 2
            )
        );
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function iterationDataIsAddedToTemplateVariableContainerIfIterationArgumentIsSet()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = array('foo' => 'bar', 'Flow' => 'Fluid', 'TYPO3' => 'rocks');
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['iteration'] = 'iteration';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse'], $this->arguments['iteration']);

        $expectedCallProtocol = array(
            array(
                'innerVariable' => 'bar',
                'iteration' => array(
                    'index' => 0,
                    'cycle' => 1,
                    'total' => 3,
                    'isFirst' => true,
                    'isLast' => false,
                    'isEven' => false,
                    'isOdd' => true
                )
            ),
            array(
                'innerVariable' => 'Fluid',
                'iteration' => array(
                    'index' => 1,
                    'cycle' => 2,
                    'total' => 3,
                    'isFirst' => false,
                    'isLast' => false,
                    'isEven' => true,
                    'isOdd' => false
                )
            ),
            array(
                'innerVariable' => 'rocks',
                'iteration' => array(
                    'index' => 2,
                    'cycle' => 3,
                    'total' => 3,
                    'isFirst' => false,
                    'isLast' => true,
                    'isEven' => false,
                    'isOdd' => true
                )
            )
        );
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function iteratedItemsAreNotCountedIfIterationArgumentIsNotSet()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\Fluid\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $mockItems = $this->getMockBuilder(\ArrayObject::class)->setMethods(['count'])->disableOriginalConstructor()->getMock();
        $mockItems->expects($this->never())->method('count');
        $this->arguments['each'] = $mockItems;
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);
    }
}
