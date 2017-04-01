<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the Apache2 access restriction publisher
 *
 */
class Apache2AccessRestrictionPublisherTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     */
    public function setUp()
    {
        vfsStream::setup('Foo');
    }

    /**
     * @test
     */
    public function publishAccessRestrictionsForPathPublishesAHtaccessFileInTheGivenDirectory()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.234';

        $publisher = $this->getAccessibleMock('TYPO3\Flow\Security\Authorization\Resource\Apache2AccessRestrictionPublisher', array('dummy'));
        $publisher->publishAccessRestrictionsForPath('vfs://Foo/');

        $expectedFileContents = 'Deny from all' . chr(10) . 'Allow from 192.168.1.234';

        $this->assertFileExists('vfs://Foo/.htaccess');
        $this->assertEquals($expectedFileContents, file_get_contents('vfs://Foo/.htaccess'));
    }
}
