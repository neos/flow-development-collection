<?php
namespace TYPO3\Flow\Tests\Functional\ResourceManagement;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\ResourceManagement\ResourceManager;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for resources
 */
class PersistentResourceTest extends FunctionalTestCase
{
    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
    }

    /**
     * @test
     */
    public function fileGetContentsReturnFixtureContentForResourceUri()
    {
        $resource = $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt');
        $this->assertEquals('fixture', file_get_contents('resource://' . $resource->getSha1()));
    }
}
