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
     * @param string $packageKey Package key where the resource is from.
     * @param string $pathAndFilename The path and filename of the resource. Has to start with "Public/..." as private resources do not have a uri.
     * @param bool $localize If enabled localizing of the resource is attempted by adding locales from the current locale-chain between filename and extension.
     * @return string
     */
    public function uri(string $packageKey, string $pathAndFilename, bool $localize = false): string
    {
        $resourcePath = $this->getLocalizedResourcePath($packageKey, $pathAndFilename, $localize);
        return $this->resourceManager->getPublicPackageResourceUriByPath($resourcePath);
    }

    /**
     * Get the content of a package resource
     *
     * @param string $packageKey Package key where the resource is from.
     * @param string $pathAndFilename The path and filename of the resource. Starting with "Public/..." or "Private/..."
     * @param bool $localize If enabled localizing of the resource is attempted by adding locales from the current locale-chain between filename and extension.
     * @return string
     */
    public function content(string $packageKey, string $pathAndFilename, bool $localize = false): string
    {
        $resourcePath = $this->getLocalizedResourcePath($packageKey, $pathAndFilename, $localize);
        return file_get_contents($resourcePath) ?: '';
    }

    /**
     * Get a resource://.. url for the given arguments and apply localization if needed
     *
     * @param string $packageKey
     * @param string $pathAndFilename
     * @param bool $localize
     * @return string
     */
    protected function getLocalizedResourcePath(string $packageKey, string $pathAndFilename, bool $localize = false): string
    {
        $resourcePath = sprintf('resource://%s/%s', $packageKey, $pathAndFilename);
        if ($localize === true) {
            $localizedResourcePathData = $this->i18nService->getLocalizedFilename($resourcePath);
            $resourcePath = $localizedResourcePathData[0] ?? $resourcePath;
        }
        return $resourcePath;
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
