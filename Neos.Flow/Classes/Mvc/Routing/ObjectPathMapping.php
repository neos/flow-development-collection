<?php
namespace Neos\Flow\Mvc\Routing;

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
 * An ObjectPathMapping model
 * This contains the URI representation of an object (pathSegment)
 *
 * @Flow\Entity
 * @ORM\Table(
 *  indexes={
 *      @ORM\Index(columns={"identifier", "uripattern", "pathsegment"})
 *  }
 * )
 */
class ObjectPathMapping
{
    /**
     * Class name of the object this mapping belongs to
     *
     * @var string
     * @phpstan-var class-string
     * @ORM\Id
     * @Flow\Validate(type="NotEmpty")
     */
    protected $objectType;

    /**
     * Pattern of the path segment (for example "{date}/{title}")
     *
     * @var string
     * @ORM\Id
     * @Flow\Validate(type="NotEmpty")
     */
    protected $uriPattern;

    /**
     * Path segment (URI representation) of the object this mapping belongs to
     *
     * @var string
     * @ORM\Id
     * @Flow\Validate(type="NotEmpty")
     */
    protected $pathSegment;

    /**
     * Identifier of the object this mapping belongs to
     *
     * @var string
     */
    protected $identifier;

    /**
     * @param string $pathSegment
     */
    public function setPathSegment($pathSegment): void
    {
        $this->pathSegment = $pathSegment;
    }

    /**
     * @return string
     */
    public function getPathSegment()
    {
        return $this->pathSegment;
    }

    /**
     * @param string $uriPattern
     */
    public function setUriPattern($uriPattern): void
    {
        $this->uriPattern = $uriPattern;
    }

    /**
     * @return string
     */
    public function getUriPattern()
    {
        return $this->uriPattern;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param class-string $objectType
     */
    public function setObjectType($objectType): void
    {
        $this->objectType = $objectType;
    }

    /**
     * @return class-string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }
}
