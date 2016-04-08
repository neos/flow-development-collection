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

use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\Now;

/**
 * Test case for the RedirectionService class
 */
class RedirectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetSourceAndTargetUriPath()
    {
        $redirect = new Redirect('source/path', 'target/path', 301);
        $this->assertSame('source/path', $redirect->getSourceUriPath());
        $this->assertSame('target/path', $redirect->getTargetUriPath());
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame(null, $redirect->getHost());
        $this->assertSame(null, $redirect->getLastHit());
        $this->assertSame(0, $redirect->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorTrimSlashInSourceAndTargetUriPath()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 301);
        $this->assertSame('source/path', $redirect->getSourceUriPath());
        $this->assertSame('target/path', $redirect->getTargetUriPath());
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame(null, $redirect->getHost());
        $this->assertSame(null, $redirect->getLastHit());
        $this->assertSame(0, $redirect->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorCanSetStatusAndHost()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('source/path', $redirect->getSourceUriPath());
        $this->assertSame('target/path', $redirect->getTargetUriPath());
        $this->assertSame(303, $redirect->getStatusCode());
        $this->assertSame('www.host.com', $redirect->getHost());
        $this->assertSame(null, $redirect->getLastHit());
        $this->assertSame(0, $redirect->getHitCounter());
    }

    /**
     * @test
     */
    public function constructorSetSourceAndTargetUriPathHash()
    {
        $redirect = new Redirect('source/path', 'target/path', 301);
        $this->assertSame(md5('source/path'), $redirect->getSourceUriPathHash());
        $this->assertSame(md5('target/path'), $redirect->getTargetUriPathHash());
    }

    /**
     * @test
     */
    public function constructorSetDefaultValueForCreationAndLastModificationData()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirect->getLastModificationDateTime()->getTimestamp());
        $this->assertSame($now->getTimestamp(), $redirect->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function updateSetTargetUriAndStatusCode()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $previousRedirect = clone $redirect;

        $now = new Now();

        $redirect->update('foo/bar', 301);
        $this->assertSame('foo/bar', $redirect->getTargetUriPath());
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame($now->getTimestamp(), $redirect->getLastModificationDateTime()->getTimestamp());
        $this->assertSame($previousRedirect->getCreationDateTime()->getTimestamp(), $redirect->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getCreationDateTimeReturnTheCreationDate()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirect->getCreationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getLastModificationDateTimeReturnTheLastModificationDate()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $this->assertSame($now->getTimestamp(), $redirect->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getSourceUriPathReturnTheSourceUriPath()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('source/path', $redirect->getSourceUriPath());
    }

    /**
     * @test
     */
    public function getSourceUriPathHashReturnTheSourceUriPathHash()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(md5('source/path'), $redirect->getSourceUriPathHash());
    }

    /**
     * @test
     */
    public function getTargetUriPathReturnTheTargetUriPath()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('target/path', $redirect->getTargetUriPath());
    }

    /**
     * @test
     */
    public function getTargetUriPathHashReturnTheTargetUriPathHash()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(md5('target/path'), $redirect->getTargetUriPathHash());
    }

    /**
     * @test
     */
    public function setTargetUriPathSetUriPathAndLastModificationDate()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $redirect->setTargetUriPath('new/target/uri/path');
        $this->assertSame('new/target/uri/path', $redirect->getTargetUriPath());
        $this->assertSame(md5('new/target/uri/path'), $redirect->getTargetUriPathHash());
        $this->assertSame($now->getTimestamp(), $redirect->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function setStatusCodeSetStatusAndLastModificationDate()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $now = new Now();
        $redirect->setStatusCode(301);
        $this->assertSame(301, $redirect->getStatusCode());
        $this->assertSame($now->getTimestamp(), $redirect->getLastModificationDateTime()->getTimestamp());
    }

    /**
     * @test
     */
    public function getStatusCodeReturnTheStatusCode()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame(303, $redirect->getStatusCode());
    }

    /**
     * @test
     */
    public function getHostReturnTheHost()
    {
        $redirect = new Redirect('/source/path/', '/target/path/', 303, 'www.host.com');
        $this->assertSame('www.host.com', $redirect->getHost());
    }
}
