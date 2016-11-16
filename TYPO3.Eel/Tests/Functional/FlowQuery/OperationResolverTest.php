<?php
namespace TYPO3\Eel\Tests\Functional\FlowQuery;

use TYPO3\Eel\FlowQuery\OperationResolver;
use TYPO3\Eel\FlowQuery\OperationResolverInterface;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Test cases for operation resolver
 */
class OperationResolverTest extends FunctionalTestCase
{
    /**
     * @var OperationResolverInterface
     */
    protected $operationResolver;


    public function setUp()
    {
        parent::setUp();
        $this->operationResolver = $this->objectManager->get(OperationResolver::class);
    }

    /**
     * @test
     */
    public function isFinalOperationReturnsTrueForFinalOperations()
    {
        $this->assertTrue($this->operationResolver->isFinalOperation('exampleFinalOperation'));
    }

    /**
     * @test
     */
    public function isFinalOperationReturnsFalseForNonFinalOperations()
    {
        $this->assertFalse($this->operationResolver->isFinalOperation('exampleNonFinalOperation'));
    }

    /**
     * @test
     */
    public function higherPriorityOverridesLowerPriority()
    {
        $this->assertInstanceOf(Fixtures\ExampleFinalOperationWithHigherPriority::class, $this->operationResolver->resolveOperation('exampleFinalOperation', []));
    }
}
