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

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * A sample entity for tests
 *
 * @Flow\Entity
 */
class Image
{
    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $data;

    /**
     * @Flow\Transient
     * @var CleanupObject
     */
    protected $relatedObject;

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return CleanupObject
     */
    public function getRelatedObject()
    {
        return $this->relatedObject;
    }

    /**
     * @param CleanupObject $relatedObject
     */
    public function setRelatedObject(CleanupObject $relatedObject = null)
    {
        $this->relatedObject = $relatedObject;
    }

    public function shutdownObject()
    {
        if ($this->relatedObject instanceof CleanupObject) {
            $this->relatedObject->toggleState();
        }
    }
}
