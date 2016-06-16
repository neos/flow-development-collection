<?php
namespace TYPO3\Flow\Tests\Functional\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilegeExpressionEvaluator;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine\SqlFilter;

/**
 *
 */
class EntityPrivilegeExpressionEvaluatorTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
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
    public function expressions()
    {
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
    public function evaluatingSomeExpressionWorks($expression, $expectedSqlCode)
    {
        $context = new \TYPO3\Eel\Context(new ConditionGenerator());

        $evaluator = new EntityPrivilegeExpressionEvaluator();
        $result = $evaluator->evaluate($expression, $context);

        $entityManager = $this->objectManager->get(\Doctrine\Common\Persistence\ObjectManager::class);
        $sqlFilter = new SqlFilter($entityManager);

        $this->assertEquals(\TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity::class, $result['entityType']);
        $this->assertEquals($expectedSqlCode, $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata(\TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity::class), 't0'));
    }
}
