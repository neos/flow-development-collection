<?php
namespace TYPO3\FLOW3\Persistence\Generic;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The QueryFactory used to create queries against the storage backend
 *
 * @api
 */
class QueryFactory implements \TYPO3\FLOW3\Persistence\Generic\QueryFactoryInterface {

	/**
	 * Creates a query object working on the given class name
	 *
	 * @param string $className
	 * @return \TYPO3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function create($className) {
		return new \TYPO3\FLOW3\Persistence\Generic\Query($className);
	}

}
?>