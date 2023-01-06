<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

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

/**
 * A sample entity for tests
 */
#[Flow\Entity]
class Image
{
    #[ORM\Column(nullable: true)]
    protected string $data;

    #[Flow\Transient]
    protected CleanupObject $relatedObject;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getRelatedObject(): CleanupObject
    {
        return $this->relatedObject;
    }

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
