<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Events;

/**
 * A sample event subscriber
 *
 * @Flow\Scope("singleton")
 */
class EventSubscriber implements \Doctrine\Common\EventSubscriber {

	public $preFlushCalled = FALSE;

	public $onFlushCalled = FALSE;

	public $postFlushCalled = FALSE;

	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	function getSubscribedEvents() {
		return array(Events::preFlush, Events::onFlush, Events::postFlush);
	}

	public function preFlush(\Doctrine\ORM\Event\PreFlushEventArgs $args) {
		$this->preFlushCalled = TRUE;
	}

	public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $args) {
		$this->onFlushCalled = TRUE;
	}

	public function postFlush(\Doctrine\ORM\Event\PostFlushEventArgs $args) {
		$this->postFlushCalled = TRUE;
	}

}
