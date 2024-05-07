<?php

declare(strict_types=1);

namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Flexible array sorter that sorts an array according to a "position" metadata.
 * The expected format for the $subject is:
 *
 * array(
 *   array(
 *     'foo' => 'bar'
 *     'position' => '<position-string>',
 *   ),
 *   array(
 *     'foo' => 'baz'
 *     'position' => '<position-string>',
 *   ),
 * )
 *
 * The <position-string> supports one of the following syntax:
 *  start (<weight>)
 *  end (<weight>)
 *  before <key> (<weight>)
 *  after <key> (<weight>)
 *  <numerical-order>
 *
 * where "weight" is the priority that defines which of two conflicting positions overrules the other,
 * "key" is a string that references another key in the $subject
 * and "numerical-order" is an integer that defines the order independently of the other keys.
 *
 * With the $positionPropertyPath parameter the property path to the position string can be changed.
 */
final class PositionalArraySorter
{
    private array $startKeys;

    private array $middleKeys;

    private array $endKeys;

    private array $beforeKeys;

    private array $afterKeys;

    /**
     * @param array $subject The source array to sort
     * @param string $positionPropertyPath optional property path to the string that contains the position
     * @param bool $removeNullValues if set to TRUE (default), null-values of the subject are removed
     */
    public function __construct(
        private readonly array $subject,
        private readonly string $positionPropertyPath = 'position',
        private readonly bool $removeNullValues = true,
    ) {
    }

    /**
     * Returns a sorted copy of the subject array
     *
     * @return array
     * @throws Exception\InvalidPositionException
     */
    public function toArray(): array
    {
        $sortedArrayKeys = $this->getSortedKeys();
        $sortedArray = [];
        foreach ($sortedArrayKeys as $key) {
            $sortedArray[$key] = $this->subject[$key];
        }
        return $sortedArray;
    }

    /**
     * Returns the keys of $this->subject sorted according to the position meta data
     *
     * TODO Detect circles in after / before dependencies (#52185)
     *
     * @return array an ordered list of keys
     * @throws Exception\InvalidPositionException if the positional string has an unsupported format
     */
    public function getSortedKeys(): array
    {
        $arrayKeysWithPosition = $this->collectArrayKeysAndPositions();
        $existingKeys = $arrayKeysWithPosition;

        $this->extractMiddleKeys($arrayKeysWithPosition);
        $this->extractStartKeys($arrayKeysWithPosition);
        $this->extractEndKeys($arrayKeysWithPosition);
        $this->extractBeforeKeys($arrayKeysWithPosition, $existingKeys);
        $this->extractAfterKeys($arrayKeysWithPosition, $existingKeys);

        if ($arrayKeysWithPosition !== []) {
            $unresolvedKey = array_key_first($arrayKeysWithPosition);
            throw new Exception\InvalidPositionException(sprintf('The positional string "%s" (defined for key "%s") is not supported.', $arrayKeysWithPosition[$unresolvedKey], $unresolvedKey), 1379429920);
        }
        return $this->generateSortedKeysMap();
    }

    /**
     * Extracts all "middle" keys from $arrayKeysWithPosition. Those are all keys with a numeric position.
     * The result is a multidimensional arrays where the KEY of each array is a PRIORITY and the VALUE is an array of matching KEYS
     * This also removes matching keys from the given $arrayKeysWithPosition
     */
    private function extractMiddleKeys(array &$arrayKeysWithPosition): void
    {
        $this->middleKeys = [];
        foreach ($arrayKeysWithPosition as $key => $position) {
            if (!is_numeric($position)) {
                continue;
            }
            $this->middleKeys[(int)$position][] = $key;
            unset($arrayKeysWithPosition[$key]);
        }
        ksort($this->middleKeys, SORT_NUMERIC);
    }

    /**
     * Extracts all "start" keys from $arrayKeysWithPosition. Those are all keys with a position starting with "start"
     * The result is a multidimensional arrays where the KEY of each array is a PRIORITY and the VALUE is an array of matching KEYS
     * This also removes matching keys from the given $arrayKeysWithPosition
     */
    private function extractStartKeys(array &$arrayKeysWithPosition): void
    {
        $this->startKeys = [];
        foreach ($arrayKeysWithPosition as $key => $position) {
            if (preg_match('/^start(?: ([0-9]+))?$/', $position, $matches) < 1) {
                continue;
            }
            if (isset($matches[1])) {
                $this->startKeys[(int)$matches[1]][] = $key;
            } else {
                $this->startKeys[0][] = $key;
            }
            unset($arrayKeysWithPosition[$key]);
        }
        krsort($this->startKeys, SORT_NUMERIC);
    }

    /**
     * Extracts all "end" keys from $arrayKeysWithPosition. Those are all keys with a position starting with "end"
     * The result is a multidimensional arrays where the KEY of each array is a PRIORITY and the VALUE is an array of matching KEYS
     * This also removes matching keys from the given $arrayKeysWithPosition
     */
    private function extractEndKeys(array &$arrayKeysWithPosition): void
    {
        $this->endKeys = [];
        foreach ($arrayKeysWithPosition as $key => $position) {
            if (preg_match('/^end(?: ([0-9]+))?$/', $position, $matches) < 1) {
                continue;
            }
            if (isset($matches[1])) {
                $this->endKeys[(int)$matches[1]][] = $key;
            } else {
                $this->endKeys[0][] = $key;
            }
            unset($arrayKeysWithPosition[$key]);
        }
        ksort($this->endKeys, SORT_NUMERIC);
    }

    /**
     * Extracts all "before" keys from $arrayKeysWithPosition. Those are all keys with a position starting with "before"
     * The result is a multidimensional arrays where the KEY of each array is a PRIORITY and the VALUE is an array of matching KEYS
     * This also removes matching keys from the given $arrayKeysWithPosition
     */
    private function extractBeforeKeys(array &$arrayKeysWithPosition, array $existingKeys): void
    {
        $this->beforeKeys = [];
        foreach ($arrayKeysWithPosition as $key => $position) {
            if (preg_match('/^before (\S+)(?: ([0-9]+))?$/', $position, $matches) < 1) {
                continue;
            }
            if (!isset($existingKeys[$matches[1]])) {
                throw new Exception\InvalidPositionException(sprintf('The positional string "%s" (defined for key "%s") references a non-existing key.', $position, $key), 1606468589);
            }
            if (isset($matches[2])) {
                $this->beforeKeys[$matches[1]][$matches[2]][] = $key;
            } else {
                $this->beforeKeys[$matches[1]][0][] = $key;
            }
            unset($arrayKeysWithPosition[$key]);
        }
        foreach ($this->beforeKeys as $key => &$keysByPriority) {
            ksort($keysByPriority, SORT_NUMERIC);
        }
    }

    /**
     * Extracts all "after" keys from $arrayKeysWithPosition. Those are all keys with a position starting with "after"
     * The result is a multidimensional arrays where the KEY of each array is a PRIORITY and the VALUE is an array of matching KEYS
     * This also removes matching keys from the given $arrayKeysWithPosition
     */
    private function extractAfterKeys(array &$arrayKeysWithPosition, array $existingKeys): void
    {
        $this->afterKeys = [];
        foreach ($arrayKeysWithPosition as $key => $position) {
            if (preg_match('/^after (\S+)(?: ([0-9]+))?$/', $position, $matches) < 1) {
                continue;
            }
            if (!isset($existingKeys[$matches[1]])) {
                throw new Exception\InvalidPositionException(sprintf('The positional string "%s" (defined for key "%s") references a non-existing key.', $position, $key), 1606468590);
            }
            if (isset($matches[2])) {
                $this->afterKeys[$matches[1]][$matches[2]][] = $key;
            } else {
                $this->afterKeys[$matches[1]][0][] = $key;
            }
            unset($arrayKeysWithPosition[$key]);
        }
        foreach ($this->afterKeys as $key => &$keysByPriority) {
            krsort($keysByPriority, SORT_NUMERIC);
        }
    }

    /**
     * Collect the array keys inside $this->subject with each position meta-argument.
     * If there is no position but the array is numerically ordered, we use the array index as position.
     *
     * @return array an associative array where each key of $subject has a position string assigned
     */
    private function collectArrayKeysAndPositions(): array
    {
        $arrayKeysWithPosition = [];

        foreach ($this->subject as $key => $value) {
            if ($value === null && $this->removeNullValues) {
                continue;
            }
            $position = ObjectAccess::getPropertyPath($value, $this->positionPropertyPath);
            if ($position !== null) {
                $arrayKeysWithPosition[$key] = $position;
            } elseif (is_numeric($key)) {
                $arrayKeysWithPosition[$key] = $key;
            } else {
                $arrayKeysWithPosition[$key] = 0;
            }
        }

        return $arrayKeysWithPosition;
    }

    /**
     * Flattens start-, middle-, end-, before- and afterKeys to a single dimension and merges them together to a single array
     */
    private function generateSortedKeysMap(): array
    {
        $sortedKeysMap = [];

        $startKeys = $this->startKeys;
        $middleKeys = $this->middleKeys;
        $endKeys = $this->endKeys;
        $beforeKeys = $this->beforeKeys;
        $afterKeys = $this->afterKeys;
        $flattenFunction = function ($value, $key, $step) use (&$sortedKeysMap, &$beforeKeys, &$afterKeys, &$flattenFunction) {
            if (isset($beforeKeys[$value])) {
                array_walk_recursive($beforeKeys[$value], $flattenFunction, $step);
                unset($beforeKeys[$value]);
            }
            $sortedKeysMap[$step][] = $value;
            if (isset($afterKeys[$value])) {
                array_walk_recursive($afterKeys[$value], $flattenFunction, $step);
                unset($afterKeys[$value]);
            }
        };

        // 1st step: collect regular keys and process before / after if keys occurred
        array_walk_recursive($startKeys, $flattenFunction, 0);
        array_walk_recursive($middleKeys, $flattenFunction, 2);
        array_walk_recursive($endKeys, $flattenFunction, 4);

        ksort($sortedKeysMap);
        return array_merge([], ...$sortedKeysMap);
    }
}
