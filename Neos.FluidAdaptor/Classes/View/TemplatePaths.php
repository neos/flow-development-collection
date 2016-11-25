<?php
namespace Neos\FluidAdaptor\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\View\Exception\InvalidTemplateResourceException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Files;

/**
 * Class TemplatePaths
 *
 * Custom implementation for template paths resolving, one which differs from the base
 * implementation in that it is capable of resolving template paths based on TypoScript
 * configuration when given a package name, and is aware of the Frontend/Backend contexts of TYPO3.
 */
class TemplatePaths extends \TYPO3Fluid\Fluid\View\TemplatePaths
{
    /**
     * @var string
     */
    protected $templateRootPathPattern;

    /**
     * @var string
     */
    protected $layoutRootPathPattern;

    /**
     * @var string
     */
    protected $partialRootPathPattern;

    /**
     * A map of key => values to be replaced in path patterns.
     *
     * @var string[]
     */
    protected $patternReplacementVariables = [
        'format' => 'html'
    ];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var PackageManagerInterface
     */
    protected $packageManager;

    public function __construct(array $options = [])
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setOption($optionName, $optionValue);
        }
    }

    /**
     * @param PackageManagerInterface $packageManager
     */
    public function injectPackageManager(PackageManagerInterface $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @return string
     */
    public function getTemplateRootPathPattern(): string
    {
        return $this->templateRootPathPattern;
    }

    /**
     * @param string $templateRootPathPattern
     */
    public function setTemplateRootPathPattern(string $templateRootPathPattern)
    {
        $this->templateRootPathPattern = $templateRootPathPattern;
    }

    /**
     * @param string $layoutRootPathPattern
     */
    public function setLayoutRootPathPattern(string $layoutRootPathPattern)
    {
        $this->layoutRootPathPattern = $layoutRootPathPattern;
    }

    /**
     * @param string $partialRootPathPattern
     */
    public function setPartialRootPathPattern(string $partialRootPathPattern)
    {
        $this->partialRootPathPattern = $partialRootPathPattern;
    }

    /**
     * @param string $templateRootPath
     */
    public function setTemplateRootPath($templateRootPath)
    {
        $this->templateRootPaths = [$templateRootPath];
    }

    /**
     * Resolves the template root to be used inside other paths.
     *
     * @return array Path(s) to template root directory
     */
    public function getTemplateRootPaths()
    {
        if ($this->templateRootPaths !== []) {
            return $this->templateRootPaths;
        }

        if ($this->templateRootPathPattern === null) {
            return [];
        }

        $templateRootPath = $this->templateRootPathPattern;
        if (isset($this->patternReplacementVariables['packageKey'])) {
            $templateRootPath = str_replace('@packageResourcesPath', 'resource://' . $this->patternReplacementVariables['packageKey'], $templateRootPath);
        }

        return [$templateRootPath];
    }

    /**
     * @return array
     */
    public function getLayoutRootPaths()
    {
        if ($this->layoutRootPaths !== []) {
            return $this->layoutRootPaths;
        }

        if ($this->layoutRootPathPattern === null) {
            return [];
        }

        $layoutRootPath = $this->layoutRootPathPattern;
        if (isset($this->patternReplacementVariables['packageKey'])) {
            $layoutRootPath = str_replace('@packageResourcesPath', 'resource://' . $this->patternReplacementVariables['packageKey'], $layoutRootPath);
        }

        return [$layoutRootPath];
    }

    public function getPartialRootPaths()
    {
        if ($this->partialRootPaths !== []) {
            return $this->partialRootPaths;
        }

        if ($this->partialRootPathPattern === null) {
            return [];
        }

        $partialRootPath = $this->partialRootPathPattern;
        if (isset($this->patternReplacementVariables['packageKey'])) {
            $partialRootPath = str_replace('@packageResourcesPath', 'resource://' . $this->patternReplacementVariables['packageKey'], $partialRootPath);
        }

        return [$partialRootPath];
    }

    /**
     * @param string $layoutRootPath
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        $this->layoutRootPaths = [$layoutRootPath];
    }

    /**
     * @param string $partialRootPath
     */
    public function setPartialRootPath($partialRootPath)
    {
        $this->partialRootPaths = [$partialRootPath];
    }

    /**
     * @return string[]
     */
    public function getPatternReplacementVariables()
    {
        return $this->patternReplacementVariables;
    }

    /**
     * @param string[] $patternReplacementVariables
     */
    public function setPatternReplacementVariables($patternReplacementVariables)
    {
        $this->patternReplacementVariables = $patternReplacementVariables;
    }

    /**
     * Resolves a template file based on the given controller and action,
     * together with eventually defined patternReplacementVariables.
     *
     * @param string $controller
     * @param string $action
     * @param string $format
     * @return mixed|string
     * @throws Exception\InvalidTemplateResourceException
     */
    public function resolveTemplateFileForControllerAndActionAndFormat($controller, $action, $format = null)
    {
        if ($this->templatePathAndFilename) {
            return $this->templatePathAndFilename;
        }

        $action = ucfirst($action);

        $paths = $this->getTemplateRootPaths();
        if (isset($this->options['templatePathAndFilenamePattern'])) {
            $paths = $this->expandGenericPathPattern($this->options['templatePathAndFilenamePattern'], array_merge($this->patternReplacementVariables, [
                'controllerName' => $controller,
                'action' => $action,
                'format' => ($format !== null ? $format : $this->patternReplacementVariables['format'])
            ]), false, false);
        }

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new Exception\InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
    }

    /**
     * Resolve the path and file name of the layout file, based on
     * $this->options['layoutPathAndFilename'] and $this->options['layoutPathAndFilenamePattern'].
     *
     * In case a layout has already been set with setLayoutPathAndFilename(),
     * this method returns that path, otherwise a path and filename will be
     * resolved using the layoutPathAndFilenamePattern.
     *
     * @param string $layoutName Name of the layout to use. If none given, use "Default"
     * @return string Path and filename of layout files
     * @throws Exception\InvalidTemplateResourceException
     */
    public function getLayoutPathAndFilename($layoutName = 'Default')
    {
        if (isset($this->options['layoutPathAndFilename'])) {
            return $this->options['layoutPathAndFilename'];
        }
        $layoutName = ucfirst($layoutName);

        $paths = $this->getLayoutRootPaths();
        if (isset($this->options['layoutPathAndFilenamePattern'])) {
            $paths = $this->expandGenericPathPattern($this->options['layoutPathAndFilenamePattern'], array_merge($this->patternReplacementVariables, [
                'layout' => $layoutName
            ]), true, true);
        }

        foreach ($paths as $layoutPathAndFilename) {
            if (is_file($layoutPathAndFilename)) {
                return $layoutPathAndFilename;
            }
        }
        throw new Exception\InvalidTemplateResourceException('The layout files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
    }

    /**
     * Resolve the partial path and filename based on $this->options['partialPathAndFilenamePattern'].
     *
     * @param string $partialName The name of the partial
     * @return string the full path which should be used. The path definitely exists.
     * @throws InvalidTemplateResourceException
     */
    public function getPartialPathAndFilename($partialName)
    {
        $patternReplacementVariables = array_merge($this->patternReplacementVariables, [
            'partial' => $partialName,
        ]);

        if (strpos($partialName, ':') !== false) {
            list($packageKey, $actualPartialName) = explode(':', $partialName);
            $package = $this->packageManager->getPackage($packageKey);
            $patternReplacementVariables['package'] = $packageKey;
            $patternReplacementVariables['packageResourcesPath'] = $package->getResourcesPath();
            $patternReplacementVariables['partial'] = $actualPartialName;
        }

        $paths = $this->expandGenericPathPattern($this->options['partialPathAndFilenamePattern'], $patternReplacementVariables, true, true);

        foreach ($paths as $partialPathAndFilename) {
            if (is_file($partialPathAndFilename)) {
                return $partialPathAndFilename;
            }
        }

        throw new Exception\InvalidTemplateResourceException('The partial files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
    }

    /**
     * @param string $packageName
     * @return string
     */
    protected function getPackagePath($packageName)
    {
        if ($this->packageManager === null) {
            return '';
        }
        if (strpos($packageName, '/') !== false) {
            $packageName = $this->packageManager->getPackageKeyFromComposerName($packageName);
        }

        if (!$this->packageManager->isPackageActive($packageName)) {
            return '';
        }

        return $this->packageManager->getPackage($packageName)->getPackagePath();
    }

    /**
     * Sanitize a path, ensuring it is absolute and
     * if a directory, suffixed by a trailing slash.
     *
     * @param string $path
     * @return string
     */
    protected function sanitizePath($path)
    {
        if (empty($path)) {
            return '';
        }

        $path = Files::getUnixStylePath($path);
        if (is_dir($path)) {
            $path = Files::getNormalizedPath($path);
        }

        return $path;
    }

    /**
     * @param string $packageKey
     * @return string|null
     */
    protected function getPackagePrivateResourcesPath($packageKey)
    {
        if (!$this->packageManager->isPackageActive($packageKey)) {
            return null;
        }
        $packageResourcesPath = $this->packageManager->getPackage($packageKey)->getResourcesPath();

        return Files::concatenatePaths([$packageResourcesPath, 'Private']);
    }

    /**
     * Processes following placeholders inside $pattern:
     *  - "@templateRoot"
     *  - "@partialRoot"
     *  - "@layoutRoot"
     *  - "@subpackage"
     *  - "@controller"
     *  - "@format"
     *
     * This method is used to generate "fallback chains" for file system locations where a certain Partial can reside.
     *
     * If $bubbleControllerAndSubpackage is FALSE and $formatIsOptional is FALSE, then the resulting array will only have one element
     * with all the above placeholders replaced.
     *
     * If you set $bubbleControllerAndSubpackage to TRUE, then you will get an array with potentially many elements:
     * The first element of the array is like above. The second element has the @ controller part set to "" (the empty string)
     * The third element now has the @ controller part again stripped off, and has the last subpackage part stripped off as well.
     * This continues until both "@subpackage" and "@controller" are empty.
     *
     * Example for $bubbleControllerAndSubpackage is TRUE, we have the MyCompany\MyPackage\MySubPackage\Controller\MyController
     * as Controller Object Name and the current format is "html"
     *
     * If pattern is "@templateRoot/@subpackage/@controller/@action.@format", then the resulting array is:
     *  - "Resources/Private/Templates/MySubPackage/My/@action.html"
     *  - "Resources/Private/Templates/MySubPackage/@action.html"
     *  - "Resources/Private/Templates/@action.html"
     *
     * If you set $formatIsOptional to TRUE, then for any of the above arrays, every element will be duplicated  - once with "@format"
     * replaced by the current request format, and once with ."@format" stripped off.
     *
     * @param string $pattern Pattern to be resolved
     * @param array $patternReplacementVariables The variables to replace in the pattern
     * @param boolean $bubbleControllerAndSubpackage if TRUE, then we successively split off parts from "@controller" and "@subpackage" until both are empty.
     * @param boolean $formatIsOptional if TRUE, then half of the resulting strings will have ."@format" stripped off, and the other half will have it.
     * @return array unix style paths
     */
    protected function expandGenericPathPattern($pattern, array $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional)
    {
        $paths = [$pattern];
        $paths = $this->expandPatterns($paths, '@templateRoot', isset($patternReplacementVariables['templateRoot']) ? [$patternReplacementVariables['templateRoot']] : $this->getTemplateRootPaths());
        $paths = $this->expandPatterns($paths, '@partialRoot', isset($patternReplacementVariables['partialRoot']) ? [$patternReplacementVariables['partialRoot']] : $this->getPartialRootPaths());
        $paths = $this->expandPatterns($paths, '@layoutRoot', isset($patternReplacementVariables['layoutRoot']) ? [$patternReplacementVariables['layoutRoot']] : $this->getLayoutRootPaths());

        $subPackageKey = isset($patternReplacementVariables['subPackageKey']) ? $patternReplacementVariables['subPackageKey'] : '';
        $controllerName = isset($patternReplacementVariables['controllerName']) ? $patternReplacementVariables['controllerName'] : '';
        $format = isset($patternReplacementVariables['format']) ? $patternReplacementVariables['format'] : '';
        unset($patternReplacementVariables['subPackageKey']);
        unset($patternReplacementVariables['controllerName']);
        unset($patternReplacementVariables['format']);

        $paths = $this->expandSubPackageAndController($paths, $controllerName, $subPackageKey, $bubbleControllerAndSubpackage);

        if ($formatIsOptional) {
            $paths = $this->expandPatterns($paths, '.@format', ['.' . $format, '']);
            $paths = $this->expandPatterns($paths, '@format', [$format, '']);
        } else {
            $paths = $this->expandPatterns($paths, '.@format', ['.' . $format]);
            $paths = $this->expandPatterns($paths, '@format', [$format]);
        }

        foreach ($patternReplacementVariables as $variableName => $variableValue) {
            $paths = $this->replacePatternVariable($paths, $variableName, $variableValue);
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param array $paths
     * @param string $variableName
     * @param string $variableValue
     * @return array
     */
    protected function replacePatternVariable($paths, $variableName, $variableValue)
    {
        foreach ($paths as &$templatePathAndFilename) {
            $templatePathAndFilename = str_replace('@' . $variableName, $variableValue, $templatePathAndFilename);
        }

        return $paths;
    }

    /**
     * @param array $paths
     * @param string $controllerName
     * @param null $subPackageKey
     * @param boolean $bubbleControllerAndSubpackage
     * @return array
     */
    protected function expandSubPackageAndController($paths, $controllerName, $subPackageKey = null, $bubbleControllerAndSubpackage = false)
    {
        if ($bubbleControllerAndSubpackage === false) {
            $paths = $this->expandPatterns($paths, '@subpackage', [$subPackageKey]);
            $paths = $this->expandPatterns($paths, '@controller', [$controllerName]);
            return $paths;
        }

        $numberOfPathsBeforeSubpackageExpansion = count($paths);
        $subpackageKeyParts = ($subPackageKey !== null) ? explode('\\', $subPackageKey) : [];
        $numberOfSubpackageParts = count($subpackageKeyParts);
        $subpackageReplacements = [];
        for ($i = 0; $i <= $numberOfSubpackageParts; $i++) {
            $subpackageReplacements[] = implode('/', ($i < 0 ? $subpackageKeyParts : array_slice($subpackageKeyParts, $i)));
        }
        $paths = $this->expandPatterns($paths, '@subpackage', $subpackageReplacements);

        for ($i = ($numberOfPathsBeforeSubpackageExpansion - 1) * ($numberOfSubpackageParts + 1); $i >= 0; $i -= ($numberOfSubpackageParts + 1)) {
            array_splice($paths, $i, 0, str_replace('@controller', $controllerName, $paths[$i]));
        }
        $paths = $this->expandPatterns($paths, '@controller', ['']);

        return $paths;
    }

    /**
     * Expands the given $patterns by adding an array element for each $replacement
     * replacing occurrences of $search.
     *
     * @param array $patterns
     * @param string $search
     * @param array $replacements
     * @return void
     */
    protected function expandPatterns(array $patterns, $search, array $replacements)
    {
        if ($replacements === []) {
            return $patterns;
        }
        $patternsWithReplacements = [];
        foreach ($patterns as $pattern) {
            foreach ($replacements as $replacement) {
                $patternsWithReplacements[] = Files::getUnixStylePath(str_replace($search, $replacement, $pattern));
            }
        }

        return $patternsWithReplacements;
    }

    /**
     * Get a specific option of this object
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        return $this->options[$optionName];
    }

    /**
     * Set a specific option of this object
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     */
    public function setOption($optionName, $value)
    {
        $this->options[$optionName] = $value;
        if (ObjectAccess::isPropertySettable($this, $optionName)) {
            ObjectAccess::setProperty($this, $optionName, $value);
        }
    }

    /**
     * Returns a unique identifier for the given file in the format
     * <PackageKey>_<SubPackageKey>_<ControllerName>_<prefix>_<SHA1>
     * The SH1 hash is a checksum that is based on the file path and last modification date
     *
     * @param string $pathAndFilename
     * @param string $prefix
     * @return string
     * @throws InvalidTemplateResourceException
     */
    protected function createIdentifierForFile($pathAndFilename, $prefix)
    {
        $templateModifiedTimestamp = 0;
        $isStandardInput = $pathAndFilename === 'php://stdin';
        $isFile = is_file($pathAndFilename);
        if ($isStandardInput === false && $isFile === false) {
            throw new InvalidTemplateResourceException(sprintf('The fluid file "%s" was not found.', $pathAndFilename), 1475831187);
        }

        if ($isStandardInput === false) {
            $templateModifiedTimestamp = filemtime($pathAndFilename);
        }

        return sprintf('%s_%s', $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
    }
}
