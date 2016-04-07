<?php
namespace Neos\RedirectHandler\DatabaseStorage\Tests\Unit;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectionRepository;
use Neos\RedirectHandler\DatabaseStorage\RedirectionStorage;
use Neos\RedirectHandler\Redirection as RedirectionDto;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\Now;

/**
 * Test case for the RedirectionService class
 */
class RedirectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetSourceAndTargetUriPath()
    {
        $redirection = new Redirection('source/path', 'target/path', 301);
        $this->assertSame('source/path', $redirection->getSourceUriPath());
        $this->assertSame('target/path', $redirection->getTargetUriPath());
        $this->assertSame(301, $redirection->getStatusCode());
        $this->assertSame(null, $redirection->getHost());
        $this->assertSame(null, $redirection->getLastHit());
        $this->assertSame(0, $redirection->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorTrimSlashInSourceAndTargetUriPath()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 301);
        $this->assertSame('source/path', $redirection->getSourceUriPath());
        $this->assertSame('target/path', $redirection->getTargetUriPath());
        $this->assertSame(301, $redirection->getStatusCode());
        $this->assertSame(null, $redirection->getHost());
        $this->assertSame(null, $redirection->getLastHit());
        $this->assertSame(0, $redirection->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorCanSetStatusAndHost()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('source/path', $redirection->getSourceUriPath());
        $this->assertSame('target/path', $redirection->getTargetUriPath());
        $this->assertSame(303, $redirection->getStatusCode());
        $this->assertSame('www.host.com', $redirection->getHost());
        $this->assertSame(null, $redirection->getLastHit());
        $this->assertSame(0, $redirection->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorSetSourceAndTargetUriPathHash()
    {
        $redirection = new Redirection('source/path', 'target/path', 301);
        $this->assertSame(md5('source/path'), $redirection->getSourceUriPathHash());
        $this->assertSame(md5('target/path'), $redirection->getTargetUriPathHash());
    }

    /**
     * @test
     */
    public function constructorSetDefaultValueForCreationAndLastModificationData()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirection->getLastModificationDateTime()->getTimestamp());
        $this->assertSame($now->getTimestamp(), $redirection->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function updateSetTargetUriAndStatusCode()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $previousRedirection = clone $redirection;

        $now = new Now();

        $redirection->update('foo/bar', 301);
        $this->assertSame('foo/bar', $redirection->getTargetUriPath());
        $this->assertSame(301, $redirection->getStatusCode());
        $this->assertSame($now->getTimestamp(), $redirection->getLastModificationDateTime()->getTimestamp());
        $this->assertSame($previousRedirection->getCreationDateTime()->getTimestamp(), $redirection->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getCreationDateTimeReturnTheCreationDate()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirection->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getLastModificationDateTimeReturnTheLastModificationDate()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirection->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getSourceUriPathReturnTheSourceUriPath()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('source/path', $redirection->getSourceUriPath());
    }

    /**
     * @test
     */
    public function getSourceUriPathHashReturnTheSourceUriPathHash()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(md5('source/path'), $redirection->getSourceUriPathHash());
    }

    /**
     * @test
     */
    public function getTargetUriPathReturnTheTargetUriPath()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('target/path', $redirection->getTargetUriPath());
    }

    /**
     * @test
     */
    public function getTargetUriPathHashReturnTheTargetUriPathHash()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(md5('target/path'), $redirection->getTargetUriPathHash());
    }

    /**
     * @test
     */
    public function setTargetUriPathSetUriPathAndLastModificationDate()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $redirection->setTargetUriPath('new/target/uri/path');
        $this->assertSame('new/target/uri/path', $redirection->getTargetUriPath());
        $this->assertSame(md5('new/target/uri/path'), $redirection->getTargetUriPathHash());
        $this->assertSame($now->getTimestamp(), $redirection->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function setStatusCodeSetStatusAndLastModificationDate()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $redirection->setStatusCode(301);
        $this->assertSame(301, $redirection->getStatusCode());
        $this->assertSame($now->getTimestamp(), $redirection->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getStatusCodeReturnTheStatusCode()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(303, $redirection->getStatusCode());
    }

    /**
     * @test
     */
    public function getHostReturnTheHost()
    {
        $redirection = new Redirection('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('www.host.com', $redirection->getHost());
    }
}
