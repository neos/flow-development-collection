<?php
namespace Neos\Flow\Tests\Functional\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilegeExpressionEvaluator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\SqlFilter;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Eel;
use Neos\Flow\Tests\Functional\Security\Fixtures;

class EntityPrivilegeExpressionEvaluatorTest extends FunctionalTestCase
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
        if (!$this->persistenceManager instanceof PersistenceManager) {
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
        return [
            [
                'isType("Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name").equals("live")',
                '(t0.name = \'live\')'
            ],

            [
                'isType("Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name") == "live"',
                '(t0.name = \'live\')'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider expressions
     *
     * @param $expression
     */
    public function evaluatingSomeExpressionWorks($expression, $expectedSqlCode)
    {
        $context = new Eel\Context(new ConditionGenerator());

        $evaluator = new EntityPrivilegeExpressionEvaluator();
        $result = $evaluator->evaluate($expression, $context);

        $entityManager = $this->objectManager->get(ObjectManager::class);
        $sqlFilter = new SqlFilter($entityManager);

        $this->assertEquals(Fixtures\RestrictableEntity::class, $result['entityType']);
        $this->assertEquals($expectedSqlCode, $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata(Fixtures\RestrictableEntity::class), 't0'));
    }
}
