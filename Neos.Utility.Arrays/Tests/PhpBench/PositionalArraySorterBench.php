<?php

namespace Neos\Utility\Arrays\Tests\PhpBench;

use Neos\Utility\PositionalArraySorter;

/**
 *
 */
class PositionalArraySorterBench
{
    /**
     * Ideally this should be a noop as there is nothing to sort
     * @Revs(20)
     */
    public function benchEmptyArray(): void
    {
        $positionalArraySorter = new PositionalArraySorter([]);
        $positionalArraySorter->toArray();
    }

    /**
     * Ideally a noop as well because a single entry will always be firstt and last regardless of position
     * @Revs(20)
     */
    public function benchSingleEntry(): void
    {
        $positionalArraySorter = new PositionalArraySorter([
            'ten' => [
                'position' => 10,
                'value' => 10
            ]
        ]);
        $positionalArraySorter->toArray();
    }

    /**
     * Few entrires with sorting props
     * @Revs(20)
     */
    public function benchWithSortingProperties(): void
    {
        $positionalArraySorter = new PositionalArraySorter([
            'ten' => [
                'position' => 10,
                'value' => 10
            ],
            'beforeTen' => [
                'position' => 'before ten',
                'value' => 'first'
            ],
            'justSomewhere' => [
                'position' => 'end',
                'value' => 'some value'
            ],
            'theLast' => [
                'position' => 'end 9999',
                'value' => 'the lastest'
            ]
        ]);
        $positionalArraySorter->toArray();
    }

    /**
     * Few entries but without sorting props
     * @Revs(20)
     */
    public function benchWithoutSortingProperties(): void
    {
        $positionalArraySorter = new PositionalArraySorter([
            'ten' => [
                'value' => 10
            ],
            'beforeTen' => [
                'value' => 'first'
            ],
            'justSomewhere' => [
                'value' => 'some value'
            ],
            'theLast' => [
                'value' => 'the lastest'
            ]
        ]);
        $positionalArraySorter->toArray();
    }
}
