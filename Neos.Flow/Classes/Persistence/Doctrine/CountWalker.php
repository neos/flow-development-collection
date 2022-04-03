<?php
namespace Neos\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * This source file is subject to the new BSD license that is bundled     *
 * with this package in the file License-BSD.txt.                         *
 *                                                                        */

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Neos\Flow\Annotations as Flow;

/**
 * A walker to transform a select query into a count query.
 *
 * @Flow\Proxy(false)
 */
class CountWalker extends TreeWalkerAdapter
{
    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve a COUNT
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $parent = null;
        $parentName = null;
        foreach ($this->_getQueryComponents() as $dqlAlias => $qComp) {
            if ($qComp['parent'] === null && $qComp['nestingLevel'] === 0) {
                $parent = $qComp;
                $parentName = $dqlAlias;
                break;
            }
        }

        $pathExpression = new PathExpression(
            PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $parentName,
            $parent['metadata']->getSingleIdentifierFieldName()
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $AST->selectClause->selectExpressions = [
            new SelectExpression(
                new AggregateExpression('count', $pathExpression, true),
                null
            )
        ];

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $AST->orderByClause = null;
    }
}
