<?php
declare(strict_types=1);

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
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return CleanupObject
     */
    public function getRelatedObject(): CleanupObject
    {
        return $this->relatedObject;
    }

    /**
     * @param CleanupObject|null $relatedObject
     */
    public function setRelatedObject(?CleanupObject $relatedObject = null): void
    {
        $this->relatedObject = $relatedObject;
    }

    public function shutdownObject(): void
    {
        if ($this->relatedObject instanceof CleanupObject) {
            $this->relatedObject->toggleState();
        }
    }
}
