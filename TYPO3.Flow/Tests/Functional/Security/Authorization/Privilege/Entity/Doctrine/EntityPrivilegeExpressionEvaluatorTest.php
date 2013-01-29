<?php
namespace TYPO3\Flow\Tests\Functional\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilegeExpressionEvaluator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\SqlFilter;

/**
 *
 */
class EntityPrivilegeExpressionEvaluatorTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * Data provider for expressions
	 *
	 * @return array
	 */
	public function expressions() {
		return array(
			array(
				'isType("TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name").equals("live")',
				'(t0.name = \'live\')'
			),

			array(
				'isType("TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name") == "live"',
				'(t0.name = \'live\')'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider expressions
	 *
	 * @param $expression
	 */
	public function evaluatingSomeExpressionWorks($expression, $expectedSqlCode) {
		$context = new \TYPO3\Eel\Context(new ConditionGenerator());

		$evaluator = new EntityPrivilegeExpressionEvaluator();
		$result = $evaluator->evaluate($expression, $context);

		$entityManager = $this->objectManager->get('Doctrine\Common\Persistence\ObjectManager');
		$sqlFilter = new SqlFilter($entityManager);

		$this->assertEquals('TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity', $result['entityType']);
		$this->assertEquals($expectedSqlCode, $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata('TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity'), 't0'));
	}
}
