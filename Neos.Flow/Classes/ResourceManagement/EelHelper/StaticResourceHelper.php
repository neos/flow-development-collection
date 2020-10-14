<?php
declare(strict_types=1);

namespace Neos\Flow\ResourceManagement\EelHelper;

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
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\I18n\Service as I18nService;

class StaticResourceHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var I18nService
     */
    protected $i18nService;

    /**
     * Get the public uri of a package resource
     *
     * @param string $packageKey
     * @param string $pathAndFilename
     * @param bool $localize
     * @return string
     */
    public function uri(string $packageKey, string $pathAndFilename, bool $localize = false): string
    {
        $resourcePath = $this->getResourcePath($packageKey, $pathAndFilename);
        if ($localize === true) {
            $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
            $resourcePath = $localizedResourcePathData[0] ?? $resourcePath;
        }
        return $this->resourceManager->getPublicPackageResourceUriByPath($resourcePath);
    }

    /**
     * Get the content of a package resource
     *
     * @param string $packageKey
     * @param string $pathAndFilename
     * @param bool $localize
     * @return string
     */
    public function content(string $packageKey, string $pathAndFilename, bool $localize = false): string
    {
        $resourcePath = $this->getResourcePath($packageKey, $pathAndFilename);
        if ($localize === true) {
            $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
            $resourcePath = $localizedResourcePathData[0] ?? $resourcePath;
        }
        $content = file_get_contents($resourcePath);
        return $content ?: '';
    }

    /**
     * Get a resource://.. url for the given arguments
     *
     * @param string $packageKey
     * @param string $pathAndFilename
     * @return string
     */
    protected function getResourcePath(string $packageKey, string $pathAndFilename): string
    {
        return sprintf('resource://%s/%s', $packageKey, $pathAndFilename);
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
