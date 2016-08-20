<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for ViewHelperNode's evaluateBooleanExpression()
 */
class BooleanNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
     */
    protected $viewHelperNode;

    /**
     * @var \TYPO3\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $renderingContext;

    /**
     * Setup fixture
     */
    public function setUp()
    {
        $this->renderingContext = $this->createMock(\TYPO3\Fluid\Core\Rendering\RenderingContextInterface::class);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Parser\Exception
     */
    public function havingMoreThanThreeElementsInTheSyntaxTreeThrowsException()
    {
        $rootNode = $this->createMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $rootNode->expects($this->once())->method('getChildNodes')->will($this->returnValue(array(1, 2, 3, 4)));

        new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
    }

    /**
     * @test
     */
    public function comparingEqualNumbersReturnsTrue()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('=='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function comparingUnequalNumbersReturnsFalse()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('=='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('3'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsFalseIfNumbersAreEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('!='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsTrueIfNumbersAreNotEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('5'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('!='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('3'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function oddNumberModulo2ReturnsTrue()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('43'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('%'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('2'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function evenNumberModulo2ReturnsFalse()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('42'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('%'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('2'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function greaterThanReturnsTrueIfNumberIsReallyGreater()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('>'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('9'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function greaterThanReturnsFalseIfNumberIsEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('>'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnsTrueIfNumberIsReallyGreater()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('>='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('9'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnsTrueIfNumberIsEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('>='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function greaterOrEqualsReturnFalseIfNumberIsSmaller()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('>='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('11'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessThanReturnsTrueIfNumberIsReallyless()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('9'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('<'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessThanReturnsFalseIfNumberIsEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('<'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnsTrueIfNumberIsReallyLess()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('9'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('<='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnsTrueIfNumberIsEqual()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('<='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnFalseIfNumberIsBigger()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('11'));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('<='));
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('10'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function lessOrEqualsReturnFalseIfComparingWithANegativeNumber()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('11 <= -2.1'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }


    /**
     * @test
     */
    public function notEqualReturnsFalseIfComparingMatchingStrings()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' != "stringA"'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function notEqualReturnsTrueIfComparingNonMatchingStrings()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' != \'stringB\''));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfComparingNonMatchingStrings()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' == \'stringB\''));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfComparingMatchingStrings()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' == "stringA"'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * 	 * @test
     */
    public function equalsReturnsTrueIfComparingMatchingStringsWithEscapedQuotes()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'\\\'stringA\\\'\' == \'\\\'stringA\\\'\''));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfComparingStringWithNonZero()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' == 42'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfComparingStringWithZero()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('\'stringA\' == 0'));

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }


    /**
     * @test
     */
    public function objectsAreComparedStrictly()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();

        $object1Node = $this->createMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class);
        $object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

        $object2Node = $this->createMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class);
        $object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

        $rootNode->addChildNode($object1Node);
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('=='));
        $rootNode->addChildNode($object2Node);

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertFalse($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function objectsAreComparedStrictlyInUnequalComparison()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();

        $object1Node = $this->createMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class);
        $object1Node->expects($this->any())->method('evaluate')->will($this->returnValue($object1));

        $object2Node = $this->createMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class);
        $object2Node->expects($this->any())->method('evaluate')->will($this->returnValue($object2));

        $rootNode->addChildNode($object1Node);
        $rootNode->addChildNode(new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode('!='));
        $rootNode->addChildNode($object2Node);

        $booleanNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode($rootNode);
        $this->assertTrue($booleanNode->evaluate($this->renderingContext));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeBoolean()
    {
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(false));
        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(true));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeString()
    {
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(''));
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean('false'));
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean('FALSE'));

        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean('true'));
        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean('TRUE'));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsNumericValues()
    {
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(0));
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(-1));
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean('-1'));
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(-.5));

        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(1));
        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(.5));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsValuesOfTypeArray()
    {
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(array()));

        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(array('foo')));
        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(array('foo' => 'bar')));
    }

    /**
     * @test
     */
    public function convertToBooleanProperlyConvertsObjects()
    {
        $this->assertFalse(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(null));

        $this->assertTrue(\TYPO3\Fluid\Core\Parser\SyntaxTree\BooleanNode::convertToBoolean(new \stdClass()));
    }
}
