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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Exception\InvalidPolicyException;

/**
 * A condition generator used as an eel context to orchestrate the different sql condition generators.
 */
class ConditionGenerator
{
    /**
     * Entity type the currently parsed expression relates to
     * @var string
     */
    protected $entityType;

    /**
     * @param $entityType
     * @return void
     * @throws InvalidPolicyException
     */
    public function isType($entityType)
    {
        if ($this->entityType !== null) {
            throw new InvalidPolicyException('You can use only exactly one isType definition in an entity privilege matcher. Already had "' . $this->entityType . '" and got another "' . $entityType . '"!', 1402989326);
        }
        $this->entityType = $entityType;
    }

    /**
     * @param SqlGeneratorInterface $expression
     * @return NotExpressionGenerator
     */
    public function notExpression(SqlGeneratorInterface $expression)
    {
        return new NotExpressionGenerator($expression);
    }

    /**
     * @return ConjunctionGenerator
     */
    public function conjunction()
    {
        $expressions = func_get_args();
        return new ConjunctionGenerator(array_filter($expressions, function ($expression) {
            return $expression instanceof SqlGeneratorInterface;
        }));
    }

    /**
     * @return DisjunctionGenerator
     */
    public function disjunction()
    {
        $expressions = func_get_args();
        return new DisjunctionGenerator(array_filter($expressions, function ($expression) {
            return $expression instanceof SqlGeneratorInterface;
        }));
    }

    /**
     * @param string $path The property path
     * @return PropertyConditionGenerator
     */
    public function property($path)
    {
        return new PropertyConditionGenerator($path);
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }
}
