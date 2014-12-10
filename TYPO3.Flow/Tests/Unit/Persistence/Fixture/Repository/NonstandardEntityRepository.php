<?php
namespace TYPO3\Flow\Tests\Persistence\Fixture\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A repository claiming responsibility for a model that cannot be matched
 * to it via naming conventions.
 *
 * @Flow\Scope("singleton")
 */
class NonstandardEntityRepository extends \TYPO3\Flow\Persistence\Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\Flow\Tests\Persistence\Fixture\Model\Entity';

}
