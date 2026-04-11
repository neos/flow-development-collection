<?php
namespace Neos\Flow\Tests\Functional\Persistence\FixturesPHP8;

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
 * A sample entity that has a property with a Id annotation
 */
#[Flow\Entity]
class AnnotatedIdEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\SequenceGenerator(sequenceName: 'annotatedidentity_seq')]
    protected string $id;

    protected string $title;

    public function getId(): string
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
