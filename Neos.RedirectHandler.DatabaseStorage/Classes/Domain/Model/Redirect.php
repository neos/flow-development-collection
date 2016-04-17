<?php
namespace Neos\RedirectHandler\DatabaseStorage\Domain\Model;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\RedirectHandler\RedirectInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;

/**
 * A Redirect model that represents a HTTP redirect
 *
 * @see RedirectService
 *
 * @Flow\Entity
 * @ORM\Table(
 * 	indexes={
 * 		@ORM\Index(name="sourceuripathhash",columns={"targeturipathhash","host"}),
 * 		@ORM\Index(name="targeturipathhash",columns={"targeturipathhash","host"})
 * 	}
 * )
 */
class Redirect implements RedirectInterface
{
    /**
     * @var \DateTime
     */
    protected $creationDateTime;

    /**
     * @var \DateTime
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
     * Full qualified host name
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
     * @ORM\Column(nullable=true)
     */
    protected $lastHit;

    /**
     * @param string $sourceUriPath relative URI path for which a redirect should be triggered
     * @param string $targetUriPath target URI path to which a redirect should be pointed
     * @param integer $statusCode status code to be send with the redirect header
     * @param string $host Full qualified host name
     */
    public function __construct($sourceUriPath, $targetUriPath, $statusCode, $host = null)
    {
        $this->sourceUriPath = trim($sourceUriPath, '/');
        $this->sourceUriPathHash = md5($this->sourceUriPath);
        $this->setTargetUriPath($targetUriPath);
        $this->statusCode = (integer)$statusCode;
        $this->host = $host ? trim($host) : null;

        $this->hitCounter = 0;

        $this->creationDateTime = new Now();
        $this->lastModificationDateTime = new Now();
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

        $this->lastModificationDateTime = new Now();
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
     * @return string
     */
    public function getSourceUriPathHash()
    {
        return $this->sourceUriPathHash;
    }

    /**
     * @param string $targetUriPath
     * @return void
     */
    public function setTargetUriPath($targetUriPath)
    {
        $this->targetUriPath = trim($targetUriPath, '/');
        $this->targetUriPathHash = md5($this->targetUriPath);

        $this->lastModificationDateTime = new Now();
    }

    /**
     * @return string
     */
    public function getTargetUriPath()
    {
        return $this->targetUriPath;
    }

    /**
     * @return string
     */
    public function getTargetUriPathHash()
    {
        return $this->targetUriPathHash;
    }

    /**
     * @param integer $statusCode
     * @return void
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        $this->lastModificationDateTime = new Now();
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return (integer)$this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getHost()
    {
        return trim($this->host) === '' ? null : $this->host;
    }

    /**
     * @return integer
     */
    public function getHitCounter()
    {
        return $this->hitCounter;
    }

    /**
     * @return \DateTime
     */
    public function getLastHit()
    {
        return $this->lastHit;
    }

    /**
     * @return void
     */
    public function incrementHitCounter()
    {
        $this->hitCounter++;

        $this->lastHit = new Now();
    }
}
