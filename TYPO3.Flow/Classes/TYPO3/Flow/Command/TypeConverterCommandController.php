<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Property\PropertyMapper;

/**
 * Command controller for listing active type converters
 *
 * @Flow\Scope("singleton")
 */
class TypeConverterCommandController extends CommandController {

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
	public function listCommand() {
		foreach ($this->propertyMapper->getTypeConverters() as $sourceType => $targetTypePriorityAndInstance) {
			$this->outputLine();
			$this->outputLine('<b>Source type "%s":</b>', array($sourceType));

			foreach ($targetTypePriorityAndInstance as $targetType => $priorityAndInstance) {
				$this->outputFormatted('<b>Target type "%s":</b>', array($targetType), 4);

				krsort($priorityAndInstance);
				foreach ($priorityAndInstance as $priority => $instance) {
					$this->outputFormatted('%3s: %s', array($priority, get_class($instance)), 8);
				}
				$this->outputLine();
			}
			$this->outputLine();
		}
	}

}