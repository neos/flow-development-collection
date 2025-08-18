<?php
namespace Neos\FluidAdaptor\Core\Parser\SyntaxTree;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n\Service;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\Parser\Interceptor\ResourceInterceptor;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\AbstractNode;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A special ViewHelperNode that works via injections and is created by the ResourceInterceptor
 *
 * @see ResourceInterceptor
 */
class ResourceUriNode extends AbstractNode
{
    /**
     * @var ResourceManager|null
     */
    protected ?ResourceManager $resourceManager;

    protected ?Service $i18nService;

    public function injectResourceManager(ResourceManager $resourceManager): void
    {
        $this->resourceManager = $resourceManager;
    }

    public function injectService(Service $i18nService): void
    {
        $this->i18nService = $i18nService;
    }

    public function __construct(
        public readonly string $path,
        public readonly string $package
    ) {
    }

    /**
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws InvalidVariableException
     */
    public function evaluate(RenderingContextInterface $renderingContext): string
    {
        $package = $this->package;
        $path = $this->path;
        if ($package === '') {
            /** @var RenderingContext $renderingContext */
            $package = $renderingContext->getControllerContext()?->getRequest()?->getControllerPackageKey();
        }
        if (str_starts_with($path, 'resource://')) {
            try {
                [$package, $path] = $this->resourceManager->getPackageAndPathByPublicPath($path);
            } catch (Exception $e) {
                throw new InvalidVariableException(sprintf('The specified path "%s" does not point to a public resource.', $path), 1386458851, $e);
            }
        }

        $resourcePath = 'resource://' . $package . '/Public/' . $this->path;
        $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
        $matches = [];
        if (preg_match('#resource://([^/]+)/Public/(.*)#', current($localizedResourcePathData), $matches) === 1) {
            [$_, $package, $path] = $matches;
        }

        return $this->resourceManager->getPublicPackageResourceUri($package, $path);
    }
}
