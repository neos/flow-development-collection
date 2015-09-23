<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
