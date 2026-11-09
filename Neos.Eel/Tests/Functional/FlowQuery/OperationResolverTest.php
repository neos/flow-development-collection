<?php

declare(strict_types=1);

namespace Neos\Eel\Tests\Functional\FlowQuery;

use Neos\Eel\FlowQuery\OperationResolver;
use Neos\Eel\FlowQuery\OperationResolverInterface;
use Neos\Eel\Tests\Functional\FlowQuery\Fixtures\ExampleFinalOperationWithHigherPriority;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test cases for operation resolver
 */
final class OperationResolverTest extends FunctionalTestCase
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

    #[Test]
    public function isFinalOperationReturnsTrueForFinalOperations()
    {
        self::assertTrue($this->operationResolver->isFinalOperation('exampleFinalOperation'));
    }

    #[Test]
    public function isFinalOperationReturnsFalseForNonFinalOperations()
    {
        self::assertFalse($this->operationResolver->isFinalOperation('exampleNonFinalOperation'));
    }

    #[Test]
    public function higherPriorityOverridesLowerPriority()
    {
        self::assertInstanceOf(ExampleFinalOperationWithHigherPriority::class, $this->operationResolver->resolveOperation('exampleFinalOperation', []));
    }
}
