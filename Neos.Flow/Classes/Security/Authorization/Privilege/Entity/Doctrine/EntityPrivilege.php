<?php
namespace Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Neos\Eel\Context as EelContext;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Entity\EntityPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use Neos\Flow\Security\Exception\InvalidQueryRewritingConstraintException;

/**
 * A filter to rewrite doctrine queries according to the security policy.
 *
 * @Flow\Proxy(false)
 */
class EntityPrivilege extends AbstractPrivilege implements EntityPrivilegeInterface
{
    /**
     * @var boolean
     */
    protected $isEvaluated = false;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var SqlGeneratorInterface
     */
    protected $conditionGenerator;

    /**
     * @param string $entityType
     * @return boolean
     * @throws InvalidQueryRewritingConstraintException
     */
    public function matchesEntityType($entityType)
    {
        $this->evaluateMatcher();
        if ($this->entityType === null) {
            throw new InvalidQueryRewritingConstraintException('Entity type could not be determined! This might be due to an missing entity type matcher in your privilege target definition!', 1416399447);
        }
        return $this->entityType === $entityType;
    }

    /**
     * Note: The result of this method cannot be cached, as the target table alias might change for different query scenarios
     *
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function getSqlConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $this->evaluateMatcher();

        /** @var EntityManager $entityManager */
        $entityManager = $this->objectManager->get(ObjectManager::class);
        $sqlFilter = new SqlFilter($entityManager);

        if (!$this->matchesEntityType($targetEntity->getName())) {
            return null;
        }

        return $this->conditionGenerator->getSql($sqlFilter, $targetEntity, $targetTableAlias);
    }

    /**
     * parses the matcher of this privilege using Eel and extracts "entityType" and "conditionGenerator"
     *
     * @return void
     */
    protected function evaluateMatcher()
    {
        if ($this->isEvaluated) {
            return;
        }
        $context = new EelContext($this->getConditionGenerator());

        /** @var EntityPrivilegeExpressionEvaluator $evaluator */
        $evaluator = $this->objectManager->get(EntityPrivilegeExpressionEvaluator::class);
        $result = $evaluator->evaluate($this->getParsedMatcher(), $context);
        $this->entityType = $result['entityType'];
        $this->conditionGenerator = $result['conditionGenerator'] !== null ? $result['conditionGenerator'] : new TrueConditionGenerator();
        $this->isEvaluated = true;
    }

    /**
     * @return ConditionGenerator
     */
    protected function getConditionGenerator()
    {
        return new ConditionGenerator();
    }

    /**
     * Returns TRUE, if this privilege covers the given subject. As entity
     * privileges are evaluated and enforced "within the database system"
     * in SQL and not by the voting process, this method will always
     * return FALSE.
     *
     * @param PrivilegeSubjectInterface $subject
     * @return boolean
     */
    public function matchesSubject(PrivilegeSubjectInterface $subject)
    {
        return false;
    }
}
