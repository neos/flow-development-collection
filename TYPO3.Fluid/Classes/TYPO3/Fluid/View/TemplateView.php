<?php
namespace TYPO3\Fluid\View;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PositionalArraySorter;

/**
 * The main template view. Should be used as view if you want Fluid Templating
 *
 * @api
 */
class TemplateView extends AbstractTemplateView
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'templateRootPathPattern' => array('@packageResourcesPath/Private/Templates', 'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
        'partialRootPathPattern' => array('@packageResourcesPath/Private/Partials', 'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),
        'layoutRootPathPattern' => array('@packageResourcesPath/Private/Layouts', 'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"', 'string'),

        'templateRootPaths' => array(null, 'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path', 'array'),
        'partialRootPaths' => array(null, 'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path', 'array'),
        'layoutRootPaths' => array(null, 'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path', 'array'),

        'templatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.@format', 'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"', 'string'),
        'partialPathAndFilenamePattern' => array('@partialRoot/@subpackage/@partial.@format', 'Directory pattern for global partials. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"', 'string'),
        'layoutPathAndFilenamePattern' => array('@layoutRoot/@layout.@format', 'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"', 'string'),

        'templatePathAndFilename' => array(null, 'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern', 'string'),
        'layoutPathAndFilename' => array(null, 'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern', 'string'),
    );

    /**
     * Sets the path and name of of the template file. Effectively overrides the
     * dynamic resolving of a template file.
     *
     * @param string $templatePathAndFilename Template file path
     * @return void
     * @api
     */
    public function setTemplatePathAndFilename($templatePathAndFilename)
    {
        $this->options['templatePathAndFilename'] = $templatePathAndFilename;
    }

    /**
     * Sets the path and name of the layout file. Overrides the dynamic resolving of the layout file.
     *
     * @param string $layoutPathAndFilename Path and filename of the layout file
     * @return void
     * @api
     */
    public function setLayoutPathAndFilename($layoutPathAndFilename)
    {
        $this->options['layoutPathAndFilename'] = $layoutPathAndFilename;
    }

    /**
     * Set the root path to the templates.
     * If set, overrides the one determined from $this->options['templateRootPathPattern']
     *
     * @param string $templateRootPath Root path to the templates. If set, overrides the one determined from $this->templateRootPathPattern
     * @return void
     * @see setTemplateRootPaths()
     * @api
     */
    public function setTemplateRootPath($templateRootPath)
    {
        $this->setTemplateRootPaths(array($templateRootPath));
    }

    /**
     * Set the root path(s) to the templates.
     * If set, overrides the one determined from $this->options['templateRootPathPattern']
     *
     * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->options['templateRootPathPattern']
     * @return void
     * @api
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->options['templateRootPaths'] = $templateRootPaths;
    }

    /**
     * Resolves the template root to be used inside other paths.
     *
     * @return array Path(s) to template root directory
     */
    public function getTemplateRootPaths()
    {
        if ($this->options['templateRootPaths'] !== null) {
            return $this->applyArraySorting($this->options['templateRootPaths']);
        }
        /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        return array(str_replace('@packageResourcesPath', 'resource://' . $actionRequest->getControllerPackageKey(), $this->options['templateRootPathPattern']));
    }

    /**
     * Set the root path to the partials.
     * If set, overrides the one determined from $this->options['partialRootPathPattern']
     *
     * @param string $partialRootPath Root path to the templates. If set, overrides the one determined from $this->options['partialRootPathPattern']
     * @return void
     * @see setPartialRootPaths()
     * @api
     */
    public function setPartialRootPath($partialRootPath)
    {
        $this->setPartialRootPaths(array($partialRootPath));
    }

    /**
     * Set the root path(s) to the partials.
     * If set, overrides the one determined from $this->options['partialRootPathPattern']
     *
     * @param array $partialRootPaths Root paths to the partials. If set, overrides the one determined from $this->options['partialRootPathPattern']
     * @return void
     * @api
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        $this->options['partialRootPaths'] = $partialRootPaths;
    }

    /**
     * Resolves the partial root to be used inside other paths.
     *
     * @return array Path(s) to partial root directory
     */
    protected function getPartialRootPaths()
    {
        if ($this->options['partialRootPaths'] !== null) {
            return $this->applyArraySorting($this->options['partialRootPaths']);
        }
        /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        return array(str_replace('@packageResourcesPath', 'resource://' . $actionRequest->getControllerPackageKey(), $this->options['partialRootPathPattern']));
    }

    /**
     * Set the root path to the layouts.
     * If set, overrides the one determined from $this->options['layoutRootPathPattern']
     *
     * @param string $layoutRootPath Root path to the layouts. If set, overrides the one determined from $this->options['layoutRootPathPattern']
     * @return void
     * @see setLayoutRootPaths()
     * @api
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        $this->setLayoutRootPaths(array($layoutRootPath));
    }

    /**
     * Set the root path(s) to the layouts.
     * If set, overrides the one determined from $this->options['layoutRootPathPattern']
     *
     * @param array $layoutRootPaths Root paths to the layouts. If set, overrides the one determined from $this->options['layoutRootPathPattern']
     * @return void
     * @api
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        $this->options['layoutRootPaths'] = $layoutRootPaths;
    }

    /**
     * Resolves the layout root to be used inside other paths.
     *
     * @return string Path(s) to layout root directory
     */
    protected function getLayoutRootPaths()
    {
        if ($this->options['layoutRootPaths'] !== null) {
            return $this->applyArraySorting($this->options['layoutRootPaths']);
        }
        /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        return array(str_replace('@packageResourcesPath', 'resource://' . $actionRequest->getControllerPackageKey(), $this->options['layoutRootPathPattern']));
    }

    /**
     * Returns a unique identifier for the resolved template file
     * This identifier is based on the template path and last modification date
     *
     * @param string $actionName Name of the action. If NULL, will be taken from request.
     * @return string template identifier
     */
    protected function getTemplateIdentifier($actionName = null)
    {
        if ($actionName === null) {
            /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
            $actionRequest = $this->controllerContext->getRequest();
            $actionName = $actionRequest->getControllerActionName();
        }
        $templatePathAndFilename = $this->getTemplatePathAndFilename($actionName);
        $controllerName = $this->controllerContext->getRequest()->getControllerName();
        $prefix = $controllerName . '_action_' . $actionName;
        return $this->createIdentifierForFile($templatePathAndFilename, $prefix);
    }

    /**
     * Resolve the template path and filename for the given action. If $actionName
     * is NULL, looks into the current request.
     *
     * @param string $actionName Name of the action. If NULL, will be taken from request.
     * @return string Full path to template
     * @throws Exception\InvalidTemplateResourceException
     */
    protected function getTemplateSource($actionName = null)
    {
        $templatePathAndFilename = $this->getTemplatePathAndFilename($actionName);
        $templateSource = Files::getFileContents($templatePathAndFilename, FILE_TEXT);
        if ($templateSource === false) {
            throw new Exception\InvalidTemplateResourceException('"' . $templatePathAndFilename . '" is not a valid template resource URI.', 1257246929);
        }
        return $templateSource;
    }

    /**
     * Resolve the template path and filename for the given action. If $actionName
     * is NULL, looks into the current request.
     *
     * @param string $actionName Name of the action. If NULL, will be taken from request.
     * @return string Full path to template
     * @throws Exception\InvalidTemplateResourceException
     */
    protected function getTemplatePathAndFilename($actionName = null)
    {
        if ($this->options['templatePathAndFilename'] !== null) {
            return $this->options['templatePathAndFilename'];
        }
        if ($actionName === null) {
            /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
            $actionRequest = $this->controllerContext->getRequest();
            $actionName = $actionRequest->getControllerActionName();
        }
        $actionName = ucfirst($actionName);

        $paths = $this->expandGenericPathPattern($this->options['templatePathAndFilenamePattern'], false, false);
        foreach ($paths as &$templatePathAndFilename) {
            $templatePathAndFilename = str_replace('@action', $actionName, $templatePathAndFilename);
            if (is_file($templatePathAndFilename)) {
                return $templatePathAndFilename;
            }
        }
        throw new Exception\InvalidTemplateResourceException('Template could not be loaded. I tried "' . implode('", "', $paths) . '"', 1225709595);
    }

    /**
     * Returns a unique identifier for the resolved layout file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $layoutName The name of the layout
     * @return string layout identifier
     */
    protected function getLayoutIdentifier($layoutName = 'Default')
    {
        $layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
        $prefix = 'layout_' . $layoutName;
        return $this->createIdentifierForFile($layoutPathAndFilename, $prefix);
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
     * @return string contents of the layout template
     * @throws Exception\InvalidTemplateResourceException
     */
    protected function getLayoutSource($layoutName = 'Default')
    {
        $layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
        $layoutSource = Files::getFileContents($layoutPathAndFilename, FILE_TEXT);
        if ($layoutSource === false) {
            throw new Exception\InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1257246929);
        }
        return $layoutSource;
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
    protected function getLayoutPathAndFilename($layoutName = 'Default')
    {
        if ($this->options['layoutPathAndFilename'] !== null) {
            return $this->options['layoutPathAndFilename'];
        }
        $paths = $this->expandGenericPathPattern($this->options['layoutPathAndFilenamePattern'], true, true);
        $layoutName = ucfirst($layoutName);
        foreach ($paths as &$layoutPathAndFilename) {
            $layoutPathAndFilename = str_replace('@layout', $layoutName, $layoutPathAndFilename);
            if (is_file($layoutPathAndFilename)) {
                return $layoutPathAndFilename;
            }
        }
        throw new Exception\InvalidTemplateResourceException('The layout files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
    }

    /**
     * Returns a unique identifier for the resolved partial file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $partialName The name of the partial
     * @return string partial identifier
     */
    protected function getPartialIdentifier($partialName)
    {
        $partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
        $prefix = 'partial_' . $partialName;
        return $this->createIdentifierForFile($partialPathAndFilename, $prefix);
    }

    /**
     * Figures out which partial to use.
     *
     * @param string $partialName The name of the partial
     * @return string contents of the partial template
     * @throws Exception\InvalidTemplateResourceException
     */
    protected function getPartialSource($partialName)
    {
        $partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
        $partialSource = Files::getFileContents($partialPathAndFilename, FILE_TEXT);
        if ($partialSource === false) {
            throw new Exception\InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246929);
        }
        return $partialSource;
    }

    /**
     * Resolve the partial path and filename based on $this->options['partialPathAndFilenamePattern'].
     *
     * @param string $partialName The name of the partial
     * @return string the full path which should be used. The path definitely exists.
     * @throws Exception\InvalidTemplateResourceException
     */
    protected function getPartialPathAndFilename($partialName)
    {
        $paths = $this->expandGenericPathPattern($this->options['partialPathAndFilenamePattern'], true, true);
        foreach ($paths as &$partialPathAndFilename) {
            $partialPathAndFilename = str_replace('@partial', $partialName, $partialPathAndFilename);
            if (is_file($partialPathAndFilename)) {
                return $partialPathAndFilename;
            }
        }
        throw new Exception\InvalidTemplateResourceException('The partial files "' . implode('", "', $paths) . '" could not be loaded.', 1225709595);
    }

    /**
     * Apply positional array sorting to the given array and
     *
     * @param array $pathes
     * @return array
     */
    protected function applyArraySorting($pathes)
    {
        $positionalArraySorter = new PositionalArraySorter($pathes);
        return array_map(
            function ($path) {
                return (is_array($path) && array_key_exists('value', $path)) ? $path['value'] : $path;
            },
            $positionalArraySorter->toArray()
        );
    }

    /**
     * Checks whether a template can be resolved for the current request context.
     *
     * @param ControllerContext $controllerContext Controller context which is available inside the view
     * @return boolean
     */
    public function canRender(ControllerContext $controllerContext)
    {
        $this->setControllerContext($controllerContext);
        try {
            $this->getTemplateSource();
            return true;
        } catch (Exception\InvalidTemplateResourceException $e) {
            return false;
        }
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
     * @param boolean $bubbleControllerAndSubpackage if TRUE, then we successively split off parts from "@controller" and "@subpackage" until both are empty.
     * @param boolean $formatIsOptional if TRUE, then half of the resulting strings will have ."@format" stripped off, and the other half will have it.
     * @return array unix style paths
     */
    protected function expandGenericPathPattern($pattern, $bubbleControllerAndSubpackage, $formatIsOptional)
    {
        $paths = array($pattern);
        $this->expandPatterns($paths, '@templateRoot', $this->getTemplateRootPaths());
        $this->expandPatterns($paths, '@partialRoot', $this->getPartialRootPaths());
        $this->expandPatterns($paths, '@layoutRoot', $this->getLayoutRootPaths());

        /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $subpackageKey = $actionRequest->getControllerSubpackageKey();
        $controllerName = $actionRequest->getControllerName();
        if ($bubbleControllerAndSubpackage) {
            $numberOfPathsBeforeSubpackageExpansion = count($paths);
            $subpackageKeyParts = ($subpackageKey !== null) ? explode('\\', $subpackageKey) : array();
            $numberOfSubpackageParts = count($subpackageKeyParts);
            $subpackageReplacements = array();
            for ($i = 0; $i <= $numberOfSubpackageParts; $i++) {
                $subpackageReplacements[] = implode('/', ($i < 0 ? $subpackageKeyParts : array_slice($subpackageKeyParts, $i)));
            }
            $this->expandPatterns($paths, '@subpackage', $subpackageReplacements);

            for ($i = ($numberOfPathsBeforeSubpackageExpansion - 1) * ($numberOfSubpackageParts + 1); $i >= 0; $i -= ($numberOfSubpackageParts + 1)) {
                array_splice($paths, $i, 0, str_replace('@controller', $controllerName, $paths[$i]));
            }
            $this->expandPatterns($paths, '@controller', array(''));
        } else {
            $this->expandPatterns($paths, '@subpackage', array($subpackageKey));
            $this->expandPatterns($paths, '@controller', array($controllerName));
        }

        if ($formatIsOptional) {
            $this->expandPatterns($paths, '.@format', array('.' . $actionRequest->getFormat(), ''));
            $this->expandPatterns($paths, '@format', array($actionRequest->getFormat(), ''));
        } else {
            $this->expandPatterns($paths, '.@format', array('.' . $actionRequest->getFormat()));
            $this->expandPatterns($paths, '@format', array($actionRequest->getFormat()));
        }

        return array_values(array_unique($paths));
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
    protected function expandPatterns(array &$patterns, $search, array $replacements)
    {
        $patternsWithReplacements = array();
        foreach ($patterns as $pattern) {
            foreach ($replacements as $replacement) {
                $patternsWithReplacements[] = Files::getUnixStylePath(str_replace($search, $replacement, $pattern));
            }
        }
        $patterns = $patternsWithReplacements;
    }

    /**
     * Returns a unique identifier for the given file in the format
     * <PackageKey>_<SubPackageKey>_<ControllerName>_<prefix>_<SHA1>
     * The SH1 hash is a checksum that is based on the file path and last modification date
     *
     * @param string $pathAndFilename
     * @param string $prefix
     * @return string
     */
    protected function createIdentifierForFile($pathAndFilename, $prefix)
    {
        /** @var $actionRequest \TYPO3\Flow\Mvc\ActionRequest */
        $actionRequest = $this->controllerContext->getRequest();
        $packageKey = $actionRequest->getControllerPackageKey();
        $subPackageKey = $actionRequest->getControllerSubpackageKey();
        if ($subPackageKey !== null) {
            $packageKey .= '_' . $subPackageKey;
        }

        $templateModifiedTimestamp = filemtime($pathAndFilename);
        $templateIdentifier = sprintf('%s_%s_%s', $packageKey, $prefix, sha1($pathAndFilename . '|' . $templateModifiedTimestamp));
        return $templateIdentifier;
    }
}
