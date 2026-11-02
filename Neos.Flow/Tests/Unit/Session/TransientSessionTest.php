<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Session;
use Neos\Flow\Session\Exception\SessionNotStartedException;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\TransientSession;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Transient Session implementation
 */
final class TransientSessionTest extends UnitTestCase
{
    #[Test]
    public function theTransientSessionImplementsTheSessionInterface()
    {
        $session = new TransientSession();
        self::assertInstanceOf(SessionInterface::class, $session);
    }

    #[Test]
    public function aSessionIdIsGeneratedOnStartingTheSession()
    {
        $session = new TransientSession();
        $session->start();
        self::assertSame(13, strlen($session->getId()));
    }

    #[Test]
    public function tryingToGetTheSessionIdWithoutStartingTheSessionThrowsAnException()
    {
        $this->expectException(SessionNotStartedException::class);
        $session = new TransientSession();
        $session->getId();
    }

    #[Test]
    public function stringsCanBeStoredByCallingPutData()
    {
        $session = new TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        self::assertEquals('some data', $session->getData('theKey'));
    }

    #[Test]
    public function allSessionDataCanBeFlushedByCallingDestroy()
    {
        $session = new TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        $session->destroy();
        $session->start();
        self::assertNull($session->getData('theKey'));
    }

    #[Test]
    public function hasKeyReturnsTrueOrFalseAccordingToAvailableKeys()
    {
        $session = new TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        self::assertTrue($session->hasKey('theKey'));
        self::assertFalse($session->hasKey('noKey'));
    }
}
