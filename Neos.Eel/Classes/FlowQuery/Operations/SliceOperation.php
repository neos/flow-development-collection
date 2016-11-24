<?php
namespace Neos\Eel\FlowQuery\Operations;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;

/**
 * Slice the current context
 *
 * If no arguments are given, the full context is returned. Otherwise the
 * value contained in the context are sliced with offset and length.
 */
class SliceOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'slice';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments A mandatory start and optional end index in the context, negative indices indicate an offset from the start or end respectively
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $context = $flowQuery->getContext();
        if ($context instanceof \Iterator) {
            $context = iterator_to_array($context);
        }
        if (isset($arguments[0]) && isset($arguments[1])) {
            $context = array_slice($context, (integer)$arguments[0], (integer)$arguments[1] - (integer)$arguments[0]);
        } elseif (isset($arguments[0])) {
            $context = array_slice($context, (integer)$arguments[0]);
        }

        $flowQuery->setContext($context);
    }
}
