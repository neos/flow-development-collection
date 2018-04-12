<?php
namespace Neos\Eel\FlowQuery\Operations\Object;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FizzleException;
use Neos\Eel\FlowQuery\FizzleParser;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * "children" operation working on generic objects. It iterates over all
 * context elements and returns the values of the properties given in the
 * filter expression that has to be specified as argument or in a following
 * filter operation.
 */
class ChildrenOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'children';

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the filter expression to use (in index 0)
     * @return void
     * @throws FizzleException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        if (count($flowQuery->getContext()) === 0) {
            return;
        }

        if (!isset($arguments[0]) || empty($arguments[0])) {
            if ($flowQuery->peekOperationName() === 'filter') {
                $filterOperation = $flowQuery->popOperation();
                if (count($filterOperation['arguments']) === 0 || empty($filterOperation['arguments'][0])) {
                    throw new FizzleException('Filter() needs arguments if it follows an empty children(): children().filter()', 1332489382);
                }
                $selectorAndFilter = $filterOperation['arguments'][0];
            } else {
                throw new FizzleException('children() needs at least a Property Name filter specified, or must be followed by filter().', 1332489399);
            }
        } else {
            $selectorAndFilter = $arguments[0];
        }

        $parsedFilter = FizzleParser::parseFilterGroup($selectorAndFilter);

        if (count($parsedFilter['Filters']) === 0) {
            throw new FizzleException('filter needs to be specified in children()', 1332489416);
        } elseif (count($parsedFilter['Filters']) === 1) {
            $filter = $parsedFilter['Filters'][0];

            if (isset($filter['PropertyNameFilter'])) {
                $this->evaluatePropertyNameFilter($flowQuery, $filter['PropertyNameFilter']);
                if (isset($filter['AttributeFilters'])) {
                    foreach ($filter['AttributeFilters'] as $attributeFilter) {
                        $flowQuery->pushOperation('filter', [$attributeFilter['text']]);
                    }
                }
            } elseif (isset($filter['AttributeFilters'])) {
                throw new FizzleException('children() must have a property name filter and cannot only have an attribute filter.', 1332489432);
            }
        } else {
            throw new FizzleException('children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"', 1332489489);
        }
    }

    /**
     * Evaluate the property name filter by traversing to the child object. We only support
     * nested objects right now
     *
     * @param FlowQuery $query
     * @param string $propertyNameFilter
     * @return void
     */
    protected function evaluatePropertyNameFilter(FlowQuery $query, $propertyNameFilter)
    {
        $resultObjects = [];
        $resultObjectHashes = [];
        foreach ($query->getContext() as $element) {
            $subProperty = ObjectAccess::getPropertyPath($element, $propertyNameFilter);
            if (is_object($subProperty) || is_array($subProperty)) {
                if (is_array($subProperty) || $subProperty instanceof \Traversable) {
                    foreach ($subProperty as $childElement) {
                        if (!isset($resultObjectHashes[spl_object_hash($childElement)])) {
                            $resultObjectHashes[spl_object_hash($childElement)] = true;
                            $resultObjects[] = $childElement;
                        }
                    }
                } elseif (!isset($resultObjectHashes[spl_object_hash($subProperty)])) {
                    $resultObjectHashes[spl_object_hash($subProperty)] = true;
                    $resultObjects[] = $subProperty;
                }
            }
        }

        $query->setContext($resultObjects);
    }
}
