<?php
namespace Neos\Flow\Command;

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
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Property\PropertyMapper;

/**
 * Command controller for listing active type converters
 *
 * @Flow\Scope("singleton")
 */
class TypeConverterCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Lists all currently active and registered type converters
     *
     * All active converters are listed with ordered by priority and grouped by
     * source type first and target type second.
     *
     * @param string $source Filter by source
     * @param string $target Filter by target type
     * @return void
     */
    public function listCommand($source = null, $target = null)
    {
        $this->outputLine();
        if ($source !== null) {
            $this->outputLine(sprintf('<info>!!</info> Filter by source      : <comment>%s</comment>', $source));
        }
        if ($target !== null) {
            $this->outputLine(sprintf('<info>!!</info> Filter by target type : <comment>%s</comment>', $target));
        }

        $this->outputLine();

        $headers = ['Source', 'Target', 'Priority', 'ClassName'];
        $table = [];
        foreach ($this->propertyMapper->getTypeConverters() as $sourceType => $targetTypePriorityAndClassName) {
            if ($source !== null && preg_match('#' . $source . '#', $sourceType) !== 1) {
                continue;
            }
            foreach ($targetTypePriorityAndClassName as $targetType => $priorityAndClassName) {
                if ($target !== null && preg_match('#' . $target . '#', $targetType) !== 1) {
                    continue;
                }
                krsort($priorityAndClassName);
                foreach ($priorityAndClassName as $priority => $className) {
                    $table[] = [
                        $sourceType,
                        $targetType,
                        $priority,
                        $className
                    ];
                }
            }
        }

        $sourceSorting = $targetSorting = $prioritySorting = [];
        foreach ($table as $key => $row) {
            $sourceSorting[$key] = strtolower($row[0]);
            $targetSorting[$key] = strtolower($row[1]);
            $prioritySorting[$key] = $row[2];
        }

        array_multisort($sourceSorting, SORT_ASC, $targetSorting, SORT_ASC, $prioritySorting, SORT_NUMERIC, $table);

        $this->output->outputTable($table, $headers);
    }
}
