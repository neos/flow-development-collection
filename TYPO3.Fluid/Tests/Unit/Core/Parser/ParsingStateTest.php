<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser;

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
 * Testcase for ParsingState
 */
class ParsingStateTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Parsing state
     *
     * @var \TYPO3\Fluid\Core\Parser\ParsingState
     */
    protected $parsingState;

    public function setUp()
    {
        $this->parsingState = new \TYPO3\Fluid\Core\Parser\ParsingState();
    }

    public function tearDown()
    {
        unset($this->parsingState);
    }

    /**
     * @test
     */
    public function setRootNodeCanBeReadOutAgain()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->parsingState->setRootNode($rootNode);
        $this->assertSame($this->parsingState->getRootNode(), $rootNode, 'Root node could not be read out again.');
    }

    /**
     * @test
     */
    public function pushAndGetFromStackWorks()
    {
        $rootNode = new \TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode();
        $this->parsingState->pushNodeToStack($rootNode);
        $this->assertSame($rootNode, $this->parsingState->getNodeFromStack($rootNode), 'Node returned from stack was not the right one.');
        $this->assertSame($rootNode, $this->parsingState->popNodeFromStack($rootNode), 'Node popped from stack was not the right one.');
    }

    /**
     * @test
     */
    public function renderCallsTheRightMethodsOnTheRootNode()
    {
        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');

        $rootNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode');
        $rootNode->expects($this->once())->method('evaluate')->with($renderingContext)->will($this->returnValue('T3DD09 Rock!'));
        $this->parsingState->setRootNode($rootNode);
        $renderedValue = $this->parsingState->render($renderingContext);
        $this->assertEquals($renderedValue, 'T3DD09 Rock!', 'The rendered value of the Root Node is not returned by the ParsingState.');
    }
}
