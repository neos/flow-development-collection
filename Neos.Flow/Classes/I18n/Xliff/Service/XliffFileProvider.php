<?php
namespace Neos\Flow\I18n\Xliff\Service;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Xliff\Model\FileAdapter;
use Neos\Flow\I18n\Xliff\V12\XliffParser as V12XliffParser;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

/**
 * A provider service for XLIFF file objects within the application
 *
 * @Flow\Scope("singleton")
 */
class XliffFileProvider
{
    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var I18n\Service
     */
    protected $localizationService;

    /**
     * @Flow\Inject
     * @var XliffReader
     */
    protected $xliffReader;

    /**
     * @Flow\InjectConfiguration(path="i18n.globalTranslationPath")
     * @var string
     */
    protected $globalTranslationPath;

    /**
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * The path relative to a package where translation files reside.
     *
     * @var string
     */
    protected $xliffBasePath = 'Private/Translations/';

    /**
     * @var array
     */
    protected $files = [];

    /**
     * Injects the Flow_I18n_XmlModelCache cache
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function injectCache(VariableFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * When it's called, XML file is parsed (using parser set in $xmlParser)
     * or cache is loaded, if available.
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->files = $this->cache->get('translationFiles') ?: [];
    }

    /**
     * @param string $fileId
     * @param Locale $locale
     * @return array
     * @todo Add XLIFF 2.0 support
     */
    public function getMergedFileData($fileId, Locale $locale): array
    {
        if (!isset($this->files[$fileId][(string)$locale])) {
            $parsedData = [
                'fileIdentifier' => $fileId
            ];
            $localeChain = $this->localizationService->getLocaleChain($locale);
            // Walk locale chain in reverse, so that translations higher in the chain overwrite fallback translations
            foreach (array_reverse($localeChain) as $localeChainItem) {
                foreach ($this->packageManager->getFlowPackages() as $package) {
                    $translationPath = $package->getResourcesPath() . $this->xliffBasePath . $localeChainItem;
                    if (is_dir($translationPath)) {
                        $this->readDirectoryRecursively($translationPath, $parsedData, $fileId, $package->getPackageKey());
                    }
                }
                $generalTranslationPath = $this->globalTranslationPath . $localeChainItem;
                if (is_dir($generalTranslationPath)) {
                    $this->readDirectoryRecursively($generalTranslationPath, $parsedData, $fileId);
                }
            }
            $this->files[$fileId][(string)$locale] = $parsedData;
            $this->cache->set('translationFiles', $this->files);
        }

        return $this->files[$fileId][(string)$locale];
    }

    /**
     * @param string $translationPath
     * @param array $parsedData
     * @param string $fileId
     * @param string $defaultPackageName
     * @return void
     */
    protected function readDirectoryRecursively(string $translationPath, array & $parsedData, string $fileId, string $defaultPackageName = 'Neos.Flow')
    {
        foreach (Files::readDirectoryRecursively($translationPath) as $filePath) {
            $defaultSource = trim(str_replace($translationPath, '', $filePath), '/');
            $defaultSource = substr($defaultSource, 0, strrpos($defaultSource, '.'));

            $relevantOffset = null;
            $documentVersion = null;

            $this->xliffReader->readFiles(
                $filePath,
                function (\XMLReader $file, $offset, $version) use ($fileId, &$documentVersion, &$relevantOffset, $defaultPackageName, $defaultSource) {
                    $documentVersion = $version;
                    switch ($version) {
                        case '1.2':
                            $packageName = $file->getAttribute('product-name') ?: $defaultPackageName;
                            $source = $file->getAttribute('original') ?: $defaultSource;
                            break;
                        default:
                            return;
                    }
                    if ($fileId === $packageName . ':' . $source) {
                        $relevantOffset = $offset;
                    }
                }
            );
            if (!is_null($relevantOffset)) {
                $xliffParser = $this->getParser($documentVersion);
                if ($xliffParser) {
                    $fileData = $xliffParser->getFileDataFromDocument($filePath, $relevantOffset);
                    $parsedData = Arrays::arrayMergeRecursiveOverrule($parsedData, $fileData);
                }
            }
        }
    }

    /**
     * @param string $fileId
     * @param Locale $locale
     * @return FileAdapter
     */
    public function getFile($fileId, Locale $locale)
    {
        return new FileAdapter($this->getMergedFileData($fileId, $locale), $locale);
    }

    /**
     * @param string $documentVersion
     * @return null|V12XliffParser
     */
    public function getParser($documentVersion)
    {
        switch ($documentVersion) {
            case '1.2':
                return new V12XliffParser();
                break;
            default:
                return null;
        }
    }
}
