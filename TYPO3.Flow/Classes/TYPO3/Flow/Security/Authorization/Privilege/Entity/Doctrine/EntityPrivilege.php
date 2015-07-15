<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use TYPO3\Eel\Context as EelContext;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\EntityPrivilegeInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeSubjectInterface;
use TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException;

/**
 * A filter to rewrite doctrine queries according to the security policy.
 *
 * @Flow\Proxy(false)
 */
class EntityPrivilege extends AbstractPrivilege implements EntityPrivilegeInterface {

	/**
	 * @var boolean
	 */
	protected $isEvaluated = FALSE;

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
	public function matchesEntityType($entityType) {
		$this->evaluateMatcher();
		if ($this->entityType === NULL) {
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
	public function getSqlConstraint(ClassMetadata $targetEntity, $targetTableAlias) {
		$this->evaluateMatcher();

		/** @var EntityManager $entityManager */
		$entityManager = $this->objectManager->get(ObjectManager::class);
		$sqlFilter = new SqlFilter($entityManager);

		if (!$this->matchesEntityType($targetEntity->getName())) {
			return NULL;
		}

		return $this->conditionGenerator->getSql($sqlFilter, $targetEntity, $targetTableAlias);
	}

	/**
	 * parses the matcher of this privilege using Eel and extracts "entityType" and "conditionGenerator"
	 *
	 * @return void
	 */
	protected function evaluateMatcher() {
		if ($this->isEvaluated) {
			return;
		}
		$context = new EelContext($this->getConditionGenerator());

		/** @var EntityPrivilegeExpressionEvaluator $evaluator */
		$evaluator = $this->objectManager->get(EntityPrivilegeExpressionEvaluator::class);
		$result = $evaluator->evaluate($this->getParsedMatcher(), $context);
		$this->entityType = $result['entityType'];
		$this->conditionGenerator = $result['conditionGenerator'] !== NULL ? $result['conditionGenerator'] : new TrueConditionGenerator();
		$this->isEvaluated = TRUE;
	}

	/**
	 * @return ConditionGenerator
	 */
	protected function getConditionGenerator() {
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
	public function matchesSubject(PrivilegeSubjectInterface $subject) {
		return FALSE;
	}
}