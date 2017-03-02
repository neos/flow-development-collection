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

/**
 * A sample entity for tests
 *
 * @Flow\Entity
 * @ORM\InheritanceType("JOINED")
 */
class SuperEntity
{
    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $content;

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
