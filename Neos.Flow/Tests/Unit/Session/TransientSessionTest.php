<?php
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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Session;

/**
 * Testcase for the Transient Session implementation
 */
class TransientSessionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theTransientSessionImplementsTheSessionInterface()
    {
        $session = new Session\TransientSession();
        $this->assertInstanceOf(Session\SessionInterface::class, $session);
    }

    /**
     * @test
     */
    public function aSessionIdIsGeneratedOnStartingTheSession()
    {
        $session = new Session\TransientSession();
        $session->start();
        $this->assertTrue(strlen($session->getId()) == 13);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Session\Exception\SessionNotStartedException
     */
    public function tryingToGetTheSessionIdWithoutStartingTheSessionThrowsAnException()
    {
        $session = new Session\TransientSession();
        $session->getId();
    }

    /**
     * @test
     */
    public function stringsCanBeStoredByCallingPutData()
    {
        $session = new Session\TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        $this->assertEquals('some data', $session->getData('theKey'));
    }

    /**
     * @test
     */
    public function allSessionDataCanBeFlushedByCallingDestroy()
    {
        $session = new Session\TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        $session->destroy();
        $session->start();
        $this->assertNull($session->getData('theKey'));
    }

    /**
     * @test
     */
    public function hasKeyReturnsTrueOrFalseAccordingToAvailableKeys()
    {
        $session = new Session\TransientSession();
        $session->start();
        $session->putData('theKey', 'some data');
        $this->assertTrue($session->hasKey('theKey'));
        $this->assertFalse($session->hasKey('noKey'));
    }
}
