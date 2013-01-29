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
use Doctrine\ORM\EntityManager;
use TYPO3\Eel\Context as EelContext;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use TYPO3\Flow\Security\Authorization\Privilege\Entity\EntityPrivilegeInterface;
use TYPO3\Flow\Security\Exception\InvalidQueryRewritingConstraintException;

/**
 * A filter to rewrite doctrine queries according to the security policy.
 *
 * @Flow\Proxy(false)
 */
class EntityPrivilege extends AbstractPrivilege implements EntityPrivilegeInterface {

	/**
	 * @var string
	 */
	protected $entityType;

	/**
	 * @param string $entityType
	 * @return boolean
	 * @throws \Exception
	 */
	public function matchesEntityType($entityType) {
		if ($this->entityType === NULL) {
			throw new InvalidQueryRewritingConstraintException('Entity type has not been evaluated yet! This might be due to an missing entity type matcher in your privilege target definition!', 1416399447);
		}
		return $this->entityType === $entityType;
	}

	/**
	 * Note: The result of this method cannot be cached, as the target table alias might change for different query scenarios
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @return string
	 */
	public function getSqlConstraint(ClassMetadata $targetEntity, $targetTableAlias) {
		$context = new EelContext($this->getConditionGenerator());

		$evaluator = new EntityPrivilegeExpressionEvaluator();
		$result = $evaluator->evaluate($this->getParsedMatcher(), $context);

		/** @var EntityManager $entityManager */
		$entityManager = $this->objectManager->get('Doctrine\Common\Persistence\ObjectManager');
		$sqlFilter = new SqlFilter($entityManager);

		$this->entityType = $result['entityType'];
		if (!$this->matchesEntityType($targetEntity->getName())) {
			return NULL;
		}
		/** @var SqlGeneratorInterface $conditionGenerator */
		$conditionGenerator = $result['conditionGenerator'];
		if ($conditionGenerator === NULL) {
			$conditionGenerator = new AnyEntityConditionGenerator();
		}
		return $conditionGenerator->getSql($sqlFilter, $targetEntity, $targetTableAlias);
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
	 * @param mixed $subject
	 * @return boolean
	 */
	public function matchesSubject($subject) {
		return FALSE;
	}
}