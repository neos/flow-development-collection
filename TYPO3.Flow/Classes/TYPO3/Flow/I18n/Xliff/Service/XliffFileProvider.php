<?php
namespace TYPO3\Flow\I18n\Xliff\Service;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Xliff\V12\XliffParser as V12XliffParser;
#use TYPO3\Flow\I18n\Xliff\V20\XliffParser as V20XliffParser;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;

/**
 * A provider service for XLIFF file objects within the application
 *
 * @Flow\Scope("singleton")
 */
class XliffFileProvider
{

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var XliffReader
     */
    protected $xliffReader;

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
     * @todo select and implement a caching mechanism
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
        $this->files = $this->cache->get('translationFiles');
    }


    /**
     * @param string $fileId
     * @param Locale $locale
     * @return array
     */
    public function getMergedFileData($fileId, Locale $locale)
    {
        if (!isset($this->files[$fileId])) {
            $parsedData = [
                'fileIdentifier' => $fileId
            ];
            foreach ($this->packageManager->getActivePackages() as $package) {
                /** @var PackageInterface $package */
                $translationPath = $package->getResourcesPath() . $this->xliffBasePath . $locale->getLanguage();
                if (is_dir($translationPath)) {
                    foreach (Files::readDirectoryRecursively($translationPath) as $filePath) {
                        $defaultSource = trim(str_replace($translationPath, '', $filePath), '/');
                        $defaultSource = substr($defaultSource, 0, strrpos($defaultSource, '.'));
                        $defaultPackageName = $package->getPackageKey();

                        $relevantOffset = null;
                        $documentVersion = null;

                        $this->xliffReader->readFiles($filePath, function (\XMLReader $file, $offset, $version) use($fileId, &$documentVersion, &$relevantOffset, $defaultPackageName, $defaultSource) {
                            $documentVersion = $version;
                            switch ($version) {
                                case '1.2':
                                    $packageName = $file->getAttribute('product-name') ?: $defaultPackageName;
                                    $source = $file->getAttribute('original') ?: $defaultSource;
                                break;
                                default:
                                    $packageName = $defaultPackageName;
                                    $source = $defaultSource;
                            }
                            if ($fileId === $packageName . ':' . $source) {
                                $relevantOffset = $offset;
                            }
                        });

                        switch ($documentVersion) {
                            case '1.2':
                                $xliffParser = new V12XliffParser();
                                break;
                            #case '2.0':
                            #    $xliffParser = new V20XliffParser();
                            #    break;
                            default:
                                $xliffParser = new V12XliffParser();
                                continue;
                        }
                        if (!is_null($relevantOffset)) {
                            $parsedData = Arrays::arrayMergeRecursiveOverrule($parsedData, $xliffParser->getFileDataFromDocument($filePath, $relevantOffset));
                        }
                    }
                }
            }
            $this->files[$fileId] = $parsedData;
            $this->cache->set('translationFiles', $this->files);
        }

        return $this->files[$fileId];
    }
}
