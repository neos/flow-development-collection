<?php
namespace Neos\Flow\Mvc\FlashMessage;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\InvalidFlashMessageConfigurationException;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Exception\InvalidRequestPatternException;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\RequestPatternResolver;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 */
class FlashMessageService
{

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var RequestPatternResolver
     */
    protected $requestPatternResolver;

    /**
     * @Flow\InjectConfiguration(path="mvc.flashMessages.containers")
     * @var array
     */
    protected $flashMessageContainerConfiguration;

    /**
     * Runtime cache for FlashMessage storage instances indexed by the unique container name
     *
     * @var FlashMessageStorageInterface[]
     */
    private $instantiatedStorages = [];

    /**
     * Persist all FlashMessageContainers that have been instantiated during the current request cycle
     *
     * @param HttpResponse $response
     * @return void
     */
    public function persistFlashMessages(HttpResponse $response)
    {
        foreach ($this->instantiatedStorages as $containerName => $flashMessageStorage) {
            $flashMessageStorage->persist($response);
        }
    }

    /**
     * Load the FlashMessageContainer for the given request
     *
     * @param RequestInterface $request
     * @return FlashMessageContainer
     */
    public function getFlashMessageContainerForRequest(RequestInterface $request): FlashMessageContainer
    {
        if (!$request instanceof ActionRequest) {
            // TODO error handling?
            return new FlashMessageContainer();
        }
        $storage = $this->getStorageByRequest($request);
        return $storage->load($request->getHttpRequest());
    }

    /**
     * @param RequestInterface $request
     * @return FlashMessageStorageInterface
     * @throws InvalidFlashMessageConfigurationException|InvalidRequestPatternException
     */
    private function getStorageByRequest(RequestInterface $request): FlashMessageStorageInterface
    {
        $sortedContainerConfiguration = (new PositionalArraySorter($this->flashMessageContainerConfiguration))->toArray();
        foreach ($sortedContainerConfiguration as $containerName => $containerConfiguration) {
            if (isset($this->flashMessageContainers[$containerName])) {
                return $this->instantiatedStorages[$containerName];
            }
            if (!isset($containerConfiguration['storage'])) {
                throw new InvalidFlashMessageConfigurationException(sprintf('Missing "storage" option for FlashMessage container "%s"', $containerName), 1502966239);
            }
            if (isset($containerConfiguration['requestPatterns'])) {
                foreach ($containerConfiguration['requestPatterns'] as $patternName => $patternConfiguration) {
                    $patternClassName = $this->requestPatternResolver->resolveRequestPatternClass($patternConfiguration['pattern']);
                    $requestPattern = $this->objectManager->get($patternClassName, $patternConfiguration['patternOptions'] ?? []);
                    if (!$requestPattern instanceof RequestPatternInterface) {
                        throw new InvalidRequestPatternException(sprintf('Invalid request pattern configuration in setting "Neos:Flow:mvc:flashMessages:containers:%s:requestPatterns:%s": Class "%s" does not implement RequestPatternInterface', $containerName, $patternName, $patternClassName), 1502982201);
                    }
                    if (!$requestPattern->matchRequest($request)) {
                        continue 2;
                    }
                }
            }
            $this->instantiatedStorages[$containerName] = $this->objectManager->get($containerConfiguration['storage'], $containerConfiguration['storageOptions'] ?? []);
            if (!$this->instantiatedStorages[$containerName] instanceof FlashMessageStorageInterface) {
                throw new InvalidFlashMessageConfigurationException(sprintf('The configured "storage" for FlashMessage container "%s" does not implement the FlashMessageStorageInterface', $containerName), 1502966423);
            }
            return $this->instantiatedStorages[$containerName];
        }
        throw new InvalidFlashMessageConfigurationException('No FlashMessage Storage could be resolved for the current request', 1502966545);
    }


}
