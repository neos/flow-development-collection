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
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use TYPO3\Flow\Annotations as Flow;

/**
 * A sql generator to create a sql not condition.
 */
class NotExpressionGenerator implements SqlGeneratorInterface {

	/**
	 * @var SqlGeneratorInterface
	 */
	protected $expression;

	/**
	 * @param SqlGeneratorInterface $expression
	 */
	public function __construct(SqlGeneratorInterface $expression) {
		$this->expression = $expression;
	}

	/**
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param ClassMetaData $targetEntity Metadata object for the target entity to create the constraint for
	 * @param string $targetTableAlias The target table alias used in the current query
	 * @return string
	 */
	public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias) {
		return ' NOT (' . $this->expression->getSql($sqlFilter, $targetEntity, $targetTableAlias) . ')';

	}
}