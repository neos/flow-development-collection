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
 * A SQL generator to create a condition matching nothing.
 */
class FalseConditionGenerator implements SqlGeneratorInterface {

	/**
	 * Returns an SQL query part that is basically a no-op in order to match no entity
	 *
	 * @param DoctrineSqlFilter $sqlFilter
	 * @param ClassMetadata $targetEntity
	 * @param string $targetTableAlias
	 * @return string
	 */
	public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias) {
		return ' (1=0) ';
	}
}