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
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\ResourceManager;

/**
 * Resource command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class ResourceCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * Publish resources
	 *
	 * This command publishes the resources of the given or - if none was specified, all - resource collections
	 * to their respective configured publishing targets.
	 *
	 * @param string $collection If specified, only resources of this collection are published. Example: 'persistent'
	 * @return void
	 */
	public function publishCommand($collection = NULL) {
		if ($collection === NULL) {
			$collections = $this->resourceManager->getCollections();
		} else {
			$collections = array();
			$collections[$collection] = $this->resourceManager->getCollection($collection);
			if ($collections[$collection] === NULL) {
				$this->outputLine('Collection "%s" does not exist.', array($collection));
				$this->quit(1);
			}
		}

		foreach ($collections as $collection) {
			/** @var CollectionInterface  $collection */
			$this->outputLine('Publishing resources of collection "%s"', array($collection->getName()));
			$collection->publish();
		}
	}

}
