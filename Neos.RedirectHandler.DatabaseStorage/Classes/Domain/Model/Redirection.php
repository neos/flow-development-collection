<?php
namespace Neos\RedirectHandler\DatabaseStorage\Domain\Model;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * A Redirection model that represents a HTTP redirect
 *
 * @see RedirectionService
 *
 * @Flow\Entity
 * @ORM\Table(
 * 	indexes={
 * 		@ORM\Index(name="targeturipathhash",columns={"targeturipathhash","hostpattern"})
 * 	}
 * )
 */
class Redirection
{
    /**
     * @var \DateTime
     */
    protected $creationDateTime;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update", field={"sourceUriPath", "targetUriPath", "statusCode", "host"})
     */
    protected $lastModificationDateTime;

    /**
     * Auto-incrementing version of this node data, used for optimistic locking
     *
     * @ORM\Version
     * @var integer
     */
    protected $version;

    /**
     * Relative URI path for which this redirect should be triggered
     *
     * @var string
     * @ORM\Column(length=4000)
     */
    protected $sourceUriPath;

    /**
     * MD5 hash of the Source Uri Path
     *
     * @var string
     * @ORM\Column(length=32)
     * @Flow\Identity
     */
    protected $sourceUriPathHash;

    /**
     * Target URI path to which a redirect should be pointed
     *
     * @var string
     * @ORM\Column(length=500)
     */
    protected $targetUriPath;

    /**
     * MD5 hash of the Target Uri Path
     *
     * @var string
     * @ORM\Column(length=32)
     */
    protected $targetUriPathHash;

    /**
     * Status code to be send with the redirect header
     *
     * @var integer
     * @Flow\Validate(type="NumberRange", options={ "minimum"=100, "maximum"=599 })
     */
    protected $statusCode;

    /**
     * Host Pattern
     *
     * @var string
     * @ORM\Column(nullable=true)
     * @Flow\Identity
     */
    protected $host;

    /**
     * @var integer
     */
    protected $hitCounter;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update", field={"hitCounter"})
     */
    protected $lastHitCount;

    /**
     * @param string $sourceUriPath relative URI path for which a redirect should be triggered
     * @param string $targetUriPath target URI path to which a redirect should be pointed
     * @param integer $statusCode status code to be send with the redirect header
     * @param string $host Host or host pattern
     */
    public function __construct($sourceUriPath, $targetUriPath, $statusCode = 301, $host = null)
    {
        $this->creationDateTime = new \DateTime();
        $this->lastModificationDateTime = new \DateTime();
        $this->sourceUriPath = trim($sourceUriPath, '/');
        $this->sourceUriPathHash = md5($this->sourceUriPath);
        $this->setTargetUriPath($targetUriPath);
        $this->statusCode = (integer)$statusCode;
        $this->host = $host ? trim($host) : null;

        $this->hitCounter = 0;
    }

    /**
     * @param string $targetUriPath
     * @param integer $statusCode
     * @return void
     */
    public function update($targetUriPath, $statusCode)
    {
        $this->setTargetUriPath($targetUriPath);
        $this->statusCode = $statusCode;
    }

    /**
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDateTime()
    {
        return $this->creationDateTime;
    }

    /**
     * @return \DateTime
     */
    public function getLastModificationDateTime()
    {
        return $this->lastModificationDateTime;
    }

    /**
     * @return string
     */
    public function getSourceUriPath()
    {
        return $this->sourceUriPath;
    }

    /**
     * @param string $targetUriPath
     * @return void
     */
    public function setTargetUriPath($targetUriPath)
    {
        $this->targetUriPath = trim($targetUriPath, '/');
        $this->targetUriPathHash = md5($this->targetUriPath);
    }

    /**
     * @return string
     */
    public function getTargetUriPath()
    {
        return $this->targetUriPath;
    }

    /**
     * @param integer $statusCode
     * @return void
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getHitCounter()
    {
        return $this->hitCounter;
    }

    /**
     * @return \DateTime
     */
    public function getLastHitCount()
    {
        return $this->lastHitCount;
    }

    /**
     * @return void
     */
    public function incrementHitCounter()
    {
        $this->hitCounter++;
    }
}
