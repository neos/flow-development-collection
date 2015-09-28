<?php
namespace TYPO3\Flow\Tests\Unit\Resource\Publishing;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Resource\Publishing\AbstractResourcePublishingTarget;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the AbstractResourcePublishingTarget
 */
class AbstractResourcePublishingTargetTest extends UnitTestCase
{
    /**
     * @var AbstractResourcePublishingTarget|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $abstractResourcePublishingTarget;

    public function setUp()
    {
        $this->abstractResourcePublishingTarget = $this->getAccessibleMock('TYPO3\Flow\Resource\Publishing\AbstractResourcePublishingTarget', array('publishStaticResources', 'getStaticResourcesWebBaseUri', 'publishPersistentResource', 'unpublishPersistentResource', 'getPersistentResourceWebUri'));
    }

    /**
     * @return array
     */
    public function rewriteFilenameForUriDataProvider()
    {
        return array(
            array('filename' => 'some-file.pdf', 'expectedResult' => 'some-file.pdf'),
            array('filename' => 'späcial_chäracterß.pdf', 'expectedResult' => 'späcial-chäracterß.pdf'),
            array('filename' => 'привет.jpg', 'expectedResult' => 'привет.jpg'),
            array('filename' => '.jpg', 'expectedResult' => 'unnamed.jpg'),
            array('filename' => '', 'expectedResult' => 'unnamed'),
        );
    }

    /**
     * @param string $filename
     * @param string $expectedResult
     * @test
     * @dataProvider rewriteFilenameForUriDataProvider
     */
    public function rewriteFilenameForUriTests($filename, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->abstractResourcePublishingTarget->_call('rewriteFilenameForUri', $filename));
    }
}
