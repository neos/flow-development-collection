<?php
namespace TYPO3\Eel\Tests\Functional\FlowQuery;

/**
 * Test cases for operation resolver
 */
class OperationResolverTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var \TYPO3\Eel\FlowQuery\OperationResolverInterface
	 */
	protected $operationResolver;


	public function setUp() {
		parent::setUp();
		$this->operationResolver = $this->objectManager->get('TYPO3\Eel\FlowQuery\OperationResolver');
	}

	/**
	 * @test
	 */
	public function isFinalOperationReturnsTrueForFinalOperations() {
		$this->assertTrue($this->operationResolver->isFinalOperation('exampleFinalOperation'));
	}

	/**
	 * @test
	 */
	public function isFinalOperationReturnsFalseForNonFinalOperations() {
		$this->assertFalse($this->operationResolver->isFinalOperation('exampleNonFinalOperation'));
	}

	/**
	 * @test
	 */
	public function higherPriorityOverridesLowerPriority() {
		$this->assertInstanceOf('TYPO3\Eel\Tests\Functional\FlowQuery\Fixtures\ExampleFinalOperationWithHigherPriority', $this->operationResolver->resolveOperation('exampleFinalOperation', array()));
	}
}
