<?php
namespace Neos\Eel\Tests\Functional\FlowQuery;

use Neos\Eel\FlowQuery\OperationResolver;
use Neos\Eel\FlowQuery\OperationResolverInterface;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Test cases for operation resolver
 */
class OperationResolverTest extends FunctionalTestCase
{
    /**
     * @var OperationResolverInterface
     */
    protected $operationResolver;


    protected function setUp(): void
    {
        parent::setUp();
        $this->operationResolver = $this->objectManager->get(OperationResolver::class);
    }

    /**
     * @test
     */
    public function isFinalOperationReturnsTrueForFinalOperations()
    {
        self::assertTrue($this->operationResolver->isFinalOperation('exampleFinalOperation'));
    }

    /**
     * @test
     */
    public function isFinalOperationReturnsFalseForNonFinalOperations()
    {
        self::assertFalse($this->operationResolver->isFinalOperation('exampleNonFinalOperation'));
    }

    /**
     * @test
     */
    public function higherPriorityOverridesLowerPriority()
    {
        self::assertInstanceOf(Fixtures\ExampleFinalOperationWithHigherPriority::class, $this->operationResolver->resolveOperation('exampleFinalOperation', []));
    }
}
