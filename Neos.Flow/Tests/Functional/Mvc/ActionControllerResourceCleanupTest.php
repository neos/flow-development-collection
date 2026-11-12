<?php
namespace Neos\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Functional tests which make sure that resources created while mapping request arguments
 * are removed again if the request does not complete successfully.
 */
class ActionControllerResourceCleanupTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var string
     */
    protected $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoute('resourceupload', 'test/mvc/resourceupload(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'ResourceUploadTest',
            '@action' => 'success',
            '@format' => 'html'
        ]);

        $this->resourceRepository = $this->objectManager->get(ResourceRepository::class);
        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
        $this->content = 'Resource cleanup test ' . bin2hex(random_bytes(16));
    }

    protected function tearDown(): void
    {
        $this->persistenceManager->clearState();
        foreach ($this->resourceRepository->findBySha1(sha1($this->content)) as $resource) {
            $this->resourceManager->deleteResource($resource);
        }
        $this->persistenceManager->persistAll();
        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    protected function resourceSource(string $filename = 'test.txt'): array
    {
        return ['data' => base64_encode($this->content), 'filename' => $filename];
    }

    protected function post(string $action, array $arguments): ResponseInterface
    {
        $response = $this->browser->request('http://localhost/test/mvc/resourceupload/' . $action, 'POST', $arguments);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        return $response;
    }

    protected function storagePathAndFilename(): string
    {
        $sha1 = sha1($this->content);
        return FLOW_PATH_DATA . 'Persistent/Test/Resources/' . $sha1[0] . '/' . $sha1[1] . '/' . $sha1[2] . '/' . $sha1[3] . '/' . $sha1;
    }

    protected function publishedPathAndFilename(string $filename = 'test.txt'): string
    {
        return FLOW_PATH_WEB . '_Resources/Testing/Persistent/' . sha1($this->content) . '/' . $filename;
    }

    protected function assertResourceWasDiscarded(string $filename = 'test.txt'): void
    {
        self::assertSame(0, $this->resourceRepository->countBySha1AndCollectionName(sha1($this->content), 'persistent'), 'The resource object was persisted');
        self::assertFileDoesNotExist($this->storagePathAndFilename(), 'The resource data is still in the storage');
        self::assertFalse(file_exists($this->publishedPathAndFilename($filename)) || is_link($this->publishedPathAndFilename($filename)), 'The resource was published');
    }

    protected function assertResourceWasKept(string $filename = 'test.txt'): void
    {
        self::assertSame(1, $this->resourceRepository->countBySha1AndCollectionName(sha1($this->content), 'persistent'), 'The resource object was not persisted');
        self::assertFileExists($this->storagePathAndFilename(), 'The resource data is not in the storage');
        self::assertFileExists($this->publishedPathAndFilename($filename), 'The resource was not published');
    }

    /**
     * @test
     */
    public function resourceIsKeptIfTheActionSucceeds(): void
    {
        $response = $this->post('success', ['resource' => $this->resourceSource()]);
        self::assertSame('success:' . sha1($this->content), $response->getBody()->getContents());
        $this->assertResourceWasKept();
    }

    /**
     * @test
     */
    public function resourceIsKeptIfTheActionRedirects(): void
    {
        $this->browser->setFollowRedirects(false);
        $response = $this->post('redirecting', ['resource' => $this->resourceSource()]);
        self::assertSame(303, $response->getStatusCode());
        $this->assertResourceWasKept();
    }

    /**
     * @test
     */
    public function resourceIsDiscardedIfARequiredArgumentIsMissing(): void
    {
        $response = $this->post('requiredargument', ['resource' => $this->resourceSource()]);
        self::assertSame(400, $response->getStatusCode());
        $this->assertResourceWasDiscarded();
    }

    /**
     * @test
     */
    public function resourceIsDiscardedIfTheActionThrowsAnException(): void
    {
        $response = $this->post('throwing', ['resource' => $this->resourceSource()]);
        self::assertSame(500, $response->getStatusCode());
        $this->assertResourceWasDiscarded();
    }

    /**
     * @test
     */
    public function resourceIsDiscardedIfAccessToTheActionIsDenied(): void
    {
        $response = $this->post('restricted', ['resource' => $this->resourceSource()]);
        self::assertNotSame('restricted', $response->getBody()->getContents());
        $this->assertResourceWasDiscarded();
    }

    /**
     * @test
     */
    public function resourceIsKeptIfAnObjectContainingItIsValid(): void
    {
        $response = $this->post('object', ['object' => ['title' => 'Some title', 'resource' => $this->resourceSource()]]);
        self::assertSame('object:' . sha1($this->content), $response->getBody()->getContents());
        $this->assertResourceWasKept();
    }

    /**
     * @test
     */
    public function resourceIsKeptIfValidationFailsForAnotherProperty(): void
    {
        $response = $this->post('object', ['object' => ['title' => '', 'resource' => $this->resourceSource()]]);
        self::assertStringContainsString('Validation failed', $response->getBody()->getContents());
        $this->assertResourceWasKept();
    }

    /**
     * @test
     */
    public function resourceIsKeptIfMappingFailsForAnotherProperty(): void
    {
        $response = $this->post('object', ['object' => ['title' => 'Some title', 'resource' => $this->resourceSource(), 'date' => ['date' => 'not a date', 'dateFormat' => 'Y-m-d']]]);
        self::assertStringContainsString('Validation failed', $response->getBody()->getContents());
        $this->assertResourceWasKept();
    }

    /**
     * @test
     */
    public function existingResourceWithTheSameContentSurvivesDiscardingANewOne(): void
    {
        $this->resourceManager->importResourceFromContent($this->content, 'test.txt');
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $response = $this->post('requiredargument', ['resource' => $this->resourceSource('other.txt')]);
        self::assertSame(400, $response->getStatusCode());
        $this->assertResourceWasKept();
        self::assertFileDoesNotExist($this->publishedPathAndFilename('other.txt'));
    }

    /**
     * @test
     */
    public function multipleNewResourcesWithTheSameContentAreDiscarded(): void
    {
        $this->resourceManager->importResourceFromContent($this->content, 'first.txt');

        $response = $this->post('requiredargument', ['resource' => $this->resourceSource()]);
        self::assertSame(400, $response->getStatusCode());
        $this->assertResourceWasDiscarded();
    }
}
