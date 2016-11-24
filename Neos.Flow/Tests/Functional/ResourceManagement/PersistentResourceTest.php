<?php
namespace Neos\Flow\Tests\Functional\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Tests\FunctionalTestCase;

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
