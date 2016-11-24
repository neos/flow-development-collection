<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Events;

/**
 * A sample event subscriber
 *
 * @Flow\Scope("singleton")
 */
class EventSubscriber implements \Doctrine\Common\EventSubscriber
{
    public $preFlushCalled = false;

    public $onFlushCalled = false;

    public $postFlushCalled = false;

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::preFlush, Events::onFlush, Events::postFlush];
    }

    public function preFlush(\Doctrine\ORM\Event\PreFlushEventArgs $args)
    {
        $this->preFlushCalled = true;
    }

    public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $args)
    {
        $this->onFlushCalled = true;
    }

    public function postFlush(\Doctrine\ORM\Event\PostFlushEventArgs $args)
    {
        $this->postFlushCalled = true;
    }
}
