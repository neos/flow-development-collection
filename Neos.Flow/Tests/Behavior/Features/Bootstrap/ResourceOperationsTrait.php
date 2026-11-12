<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Behavior\Features\Bootstrap;

use DirectoryIterator;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Client\InternalRequestEngine;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\Storage\StorageObject;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Utility\Environment;
use Neos\Http\Factories\StreamFactoryTrait;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * A trait with shared step definitions for testing operations on (persistent) resources
 *
 * @property Environment environment
 * @property ObjectManagerInterface objectManager
 */
trait ResourceOperationsTrait
{
    use StreamFactoryTrait;

    protected ?string $currentContentHash = null;

    protected ?string $currentFixtureName = null;

    /**
     * @BeforeScenario
     */
    public function resetState(): void
    {
        if (is_dir($publicationBasePath = $this->resolvePublicationBasePath())) {
            foreach (new DirectoryIterator($publicationBasePath) as $dirInfo) {
                if (!$dirInfo->isDot()) {
                    foreach (new DirectoryIterator($dirInfo->getPathname()) as $fileInfo) {
                        if (!$fileInfo->isDot()) {
                            unlink($fileInfo->getPathname());
                        }
                    }
                    rmdir($dirInfo->getPathname());
                }
            }
        }
        $this->currentContentHash = null;
        $this->currentFixtureName = null;
    }

    /**
     * @When /^I upload fixture resource "([^"]*)" using action "([^"]*)" of controller "([^"]*)"$/
     */
    public function iUploadFixtureResourceUsingMethodOfClass(string $fixtureName, string $actionName, string $controllerName, string $additionalParameter = ''): void
    {

        $fixturePathAndName = __DIR__ . '/../../../Fixtures/'. $fixtureName;
        Assert::assertTrue(file_exists($fixturePathAndName), 'Fixture file ' . $fixturePathAndName . ' is missing.');

        $fileContent = file_get_contents($fixturePathAndName);
        $this->currentContentHash = \sha1($fileContent);
        $this->currentFixtureName = $fixtureName;

        if ($this->isolated === true) {
            try {
                $this->callStepInSubProcess(
                    __METHOD__,
                    sprintf(
                        ' %s %s %s %s %s %s %s %s',
                        'string',
                        escapeshellarg($fixtureName),
                        'string',
                        escapeshellarg($actionName),
                        'string',
                        escapeshellarg($controllerName),
                        'string',
                        escapeshellarg($additionalParameter),
                    )
                );
            } catch (ExpectationFailedException) {
                // that's fine, exceptions may be thrown
            }
        } else {
            $this->uploadFixtureResourceUsingMethodOfClass($fixtureName, $actionName, $controllerName, $additionalParameter);
        }
    }

    /**
     * @When /^I upload fixture resource "([^"]*)" using action "([^"]*)" of controller "([^"]*)" with additional parameter$/
     */
    public function iUploadFixtureResourceUsingMethodOfClassWithAdditionalParameter(string $fixtureName, string $actionName, string $controllerName): void
    {
        $this->iUploadFixtureResourceUsingMethodOfClass($fixtureName, $actionName, $controllerName, 'test');
    }

    /**
     * @Then /^I expect this resource to be persisted$/
     */
    public function iExpectThisResourceToBePersisted(): void
    {
        $resourceRepository = $this->objectManager->get(ResourceRepository::class);
        Assert::assertInstanceOf(PersistentResource::class, $resourceRepository->findOneBySha1($this->currentContentHash));
    }

    /**
     * @Then /^I expect this resource not to be persisted$/
     */
    public function iExpectThisResourceNotToBePersisted(): void
    {
        $resourceRepository = $this->objectManager->get(ResourceRepository::class);
        $actualResource = $resourceRepository->findOneBySha1($this->currentContentHash);
        Assert::assertTrue($actualResource === null, 'Resource found for hash ' . $this->currentContentHash . ' but was not expected');
    }

    /**
     * @Then /^I expect this resource to be stored/
     */
    public function iExpectThisResourceToBeStored(): void
    {
        $resourceManager = $this->objectManager->get(ResourceManager::class);
        $storedResourceObject = null;
        foreach (
            $resourceManager->getCollection(ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME)
                ->getStorage()
                ->getObjects() as $storedObject
        ) {
            if ($storedObject->getSha1() === $this->currentContentHash) {
                $storedResourceObject = $storedObject;
            }
        }

        Assert::assertInstanceOf(StorageObject::class, $storedResourceObject);
    }

    /**
     * @Then /^I expect this resource not to be stored/
     */
    public function iExpectThisResourceNotToBeStored(): void
    {
        $resourceManager = $this->objectManager->get(ResourceManager::class);
        foreach (
            $resourceManager->getCollection(ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME)
                ->getStorage()
                ->getObjects() as $storedObject
        ) {
            Assert::assertNotSame($this->currentContentHash, $storedObject->getSha1());
        }
    }

    /**
     * @Then /^I expect this resource to be published/
     */
    public function iExpectThisResourceToBePublished(): void
    {
        $expectedPublicationFileNameAndPath = $this->resolvePublicationPath();

        Assert::assertTrue(\file_exists($expectedPublicationFileNameAndPath));
    }

    /**
     * @Then /^I expect this resource not to be published/
     */
    public function iExpectThisResourceNotToBePublished(): void
    {
        $expectedPublicationFileNameAndPath = $this->resolvePublicationPath();

        Assert::assertFalse(\file_exists($expectedPublicationFileNameAndPath));
    }

    protected function resolvePublicationBasePath(): string
    {
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $publicationTarget = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.Flow.resource.collections.' . ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME . '.target'
        );
        $publicationTargetPath = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            'Neos.Flow.resource.targets.' . $publicationTarget . '.targetOptions.path',
        );

        return $publicationTargetPath;
    }

    protected function resolvePublicationPath(): string
    {
        return $this->resolvePublicationBasePath() . $this->currentContentHash . '/' . $this->currentFixtureName;
    }

    protected function uploadFixtureResourceUsingMethodOfClass(string $fixtureName, string $actionName, string $controllerName, string $additionalParameter): void
    {
        file_put_contents(FLOW_PATH_ROOT . 'wat.txt', $additionalParameter);
        $fixturePathAndName = __DIR__ . '/../../../Fixtures/'. $fixtureName;
        Assert::assertTrue(file_exists($fixturePathAndName), 'Fixture file ' . $fixturePathAndName . ' is missing.');

        $fileContent = file_get_contents($fixturePathAndName);
        $this->currentContentHash = \sha1($fileContent);

        /** @var InternalRequestEngine $internalRequestEngine */
        $internalRequestEngine = $this->objectManager->get(InternalRequestEngine::class);
        $route = new Route();
        $route->setName('resourceUpload');
        $route->setUriPattern('test/security/resourceupload/{@action}/and/{additionalParameter}');
        $route->setDefaults([
            '@package'    => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => $controllerName,
            '@format'     => 'html',
        ]);
        $internalRequestEngine->getRouter()->addRoute($route);

        /** @var Bootstrap $bootstrap */
        $bootstrap = $this->objectManager->get(Bootstrap::class);
        $bootstrap->setActiveRequestHandler(new FunctionalTestRequestHandler($bootstrap));

        $httpRequest = (new ServerRequest(
            method: 'POST',
            uri: 'http://localhost/test/security/resourceupload/' . $actionName . ($additionalParameter ? '/and/' . $additionalParameter : ''),
            headers: [],
            body: null,
            serverParams: array_filter([
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Security\Fixtures',
                '@controller' => $controllerName,
                '@action' => $actionName,
            ], fn (string $value): bool => $value !== ''),
        ))->withUploadedFiles([
            'resource' => new UploadedFile(
                streamOrFile: $this->createStream($fileContent),
                size: \strlen($fileContent),
                errorStatus: UPLOAD_ERR_OK,
                clientFilename: $fixtureName,
                clientMediaType: \mime_content_type($fixturePathAndName),
            )
        ]);

        $internalRequestEngine->sendRequest($httpRequest);
    }
}
