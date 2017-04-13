<?php
namespace TYPO3\Flow\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Property\PropertyMapper;

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
     * @return void
     */
    public function listCommand()
    {
        foreach ($this->propertyMapper->getTypeConverters() as $sourceType => $targetTypePriorityAndClassName) {
            $this->outputLine();
            $this->outputLine('<b>Source type "%s":</b>', [$sourceType]);

            foreach ($targetTypePriorityAndClassName as $targetType => $priorityAndClassName) {
                $this->outputFormatted('<b>Target type "%s":</b>', [$targetType], 4);

                krsort($priorityAndClassName);
                foreach ($priorityAndClassName as $priority => $className) {
                    $this->outputFormatted('%3s: %s', [$priority, $className], 8);
                }
                $this->outputLine();
            }
            $this->outputLine();
        }
    }
}
