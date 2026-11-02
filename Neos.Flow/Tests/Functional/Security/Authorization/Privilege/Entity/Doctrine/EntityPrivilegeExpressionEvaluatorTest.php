<?php

declare(strict_types=1);

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
use Doctrine\ORM\EntityManagerInterface;
use Neos\Eel\Context;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\ConditionGenerator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\EntityPrivilegeExpressionEvaluator;
use Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine\SqlFilter;
use Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity;
use Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityC;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class EntityPrivilegeExpressionEvaluatorTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    public static function expressions(): \Iterator
    {
        yield [
            'isType("Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name").equals("live")',
            '(t0.name = \'live\')'
        ];
        yield [
            'isType("Neos\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity") && property("name") == "live"',
            '(t0.name = \'live\')'
        ];
    }

    #[DataProvider('expressions')]
    #[Test]
    public function evaluatingSomeExpressionWorks(string $expression, string $expectedSqlCode): void
    {
        $context = new Context(new ConditionGenerator());

        $evaluator = new EntityPrivilegeExpressionEvaluator();
        $result = $evaluator->evaluate($expression, $context);

        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $sqlFilter = new SqlFilter($entityManager);

        self::assertEquals(RestrictableEntity::class, $result['entityType']);
        self::assertEquals($expectedSqlCode, $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata(RestrictableEntity::class), 't0'));
    }

    #[Test]
    public function propertyContainsExpressionGeneratesExpectedSqlFilterForOneToMany(): void
    {
        $context = new Context(new ConditionGenerator());

        $evaluator = new EntityPrivilegeExpressionEvaluator();
        $result = $evaluator->evaluate('isType("Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityC") && property("oneToManyToRelatedEntityD").contains("c1ed7ad7-3618-4e0d-bcf8-c849a505dfe1")', $context);

        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $sqlFilter = new SqlFilter($entityManager);

        self::assertEquals(TestEntityC::class, $result['entityType']);
        self::assertEquals(
            '(t0.persistence_object_identifier IN (SELECT n0_.manytoonetorelatedentityc AS sclr_0 FROM neos_flow_tests_functional_security_fixtures_testentityd n0_ WHERE n0_.persistence_object_identifier = \'c1ed7ad7-3618-4e0d-bcf8-c849a505dfe1\'))',
            $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata(TestEntityC::class), 't0')
        );
    }

    #[Test]
    public function propertyContainsExpressionGeneratesExpectedSqlFilterForManyToMany(): void
    {
        $context = new Context(new ConditionGenerator());

        $evaluator = new EntityPrivilegeExpressionEvaluator();
        $result = $evaluator->evaluate('isType("Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityC") && property("manyToManyToRelatedEntityD").contains("c1ed7ad7-3618-4e0d-bcf8-c849a505dfe1")', $context);

        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $sqlFilter = new SqlFilter($entityManager);

        self::assertEquals(TestEntityC::class, $result['entityType']);
        self::assertEquals(
            '(t0.persistence_object_identifier IN (SELECT flow_fixtures_testentityc FROM neos_flow_tests_functiona_09cce_manytomanytorelatedentityd_join WHERE flow_fixtures_testentityd = \'c1ed7ad7-3618-4e0d-bcf8-c849a505dfe1\'))',
            $result['conditionGenerator']->getSql($sqlFilter, $entityManager->getClassMetadata(TestEntityC::class), 't0')
        );
    }
}
