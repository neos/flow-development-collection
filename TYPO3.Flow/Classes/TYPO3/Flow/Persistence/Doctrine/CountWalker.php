<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * This source file is subject to the new BSD license that is bundled     *
 * with this package in the file License-BSD.txt.                             *
 *                                                                        *
 * If you did not receive a copy of the license and are unable to         *
 * obtain it through the world-wide-web, please send an email             *
 * to kontakt@beberlei.de so I can send you a copy immediately.           *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Query\AST\PathExpression;
use TYPO3\Flow\Annotations as Flow;

/**
 * A walker to transform a select query into a count query.
 *
 * @Flow\Proxy(false)
 */
class CountWalker extends \Doctrine\ORM\Query\TreeWalkerAdapter {

	/**
	 * Walks down a SelectStatement AST node, modifying it to retrieve a COUNT
	 *
	 * @param \Doctrine\ORM\Query\AST\SelectStatement $AST
	 * @return void
	 */
	public function walkSelectStatement(\Doctrine\ORM\Query\AST\SelectStatement $AST) {
		$parent = null;
		$parentName = null;
		foreach ($this->_getQueryComponents() AS $dqlAlias => $qComp) {
			if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0) {
				$parent = $qComp;
				$parentName = $dqlAlias;
				break;
			}
		}

		$pathExpression = new PathExpression(
			PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $parentName,
			$parent['metadata']->getSingleIdentifierFieldName()
		);
		$pathExpression->type = PathExpression::TYPE_STATE_FIELD;

		$AST->selectClause->selectExpressions = array(
			new \Doctrine\ORM\Query\AST\SelectExpression(
				new \Doctrine\ORM\Query\AST\AggregateExpression('count', $pathExpression, true), null
			)
		);

			// ORDER BY is not needed, only increases query execution through unnecessary sorting.
		$AST->orderByClause = null;
	}

}

?>