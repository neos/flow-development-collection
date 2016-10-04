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

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\View\ViewInterface;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;

/**
 *
 */
abstract class AbstractTemplateView extends \TYPO3Fluid\Fluid\View\AbstractTemplateView implements ViewInterface
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     * Syntax example:
     *     [
     *         'someOptionName' => ['defaultValue', 'some description', 'string'),
     *         'someOtherOptionName' => ['defaultValue', some description', integer),
     *         ...
     *     )
     *
     * @var array
     */
    protected $supportedOptions = [
        'templateRootPathPattern' => [
            '@packageResourcesPath/Private/Templates',
            'Pattern to be resolved for "@templateRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'partialRootPathPattern' => [
            '@packageResourcesPath/Private/Partials',
            'Pattern to be resolved for "@partialRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'layoutRootPathPattern' => [
            '@packageResourcesPath/Private/Layouts',
            'Pattern to be resolved for "@layoutRoot" in the other patterns. Following placeholders are supported: "@packageResourcesPath"',
            'string'
        ],
        'templateRootPaths' => [
            [],
            'Path(s) to the template root. If NULL, then $this->options["templateRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'partialRootPaths' => [
            [],
            'Path(s) to the partial root. If NULL, then $this->options["partialRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'layoutRootPaths' => [
            [],
            'Path(s) to the layout root. If NULL, then $this->options["layoutRootPathPattern"] will be used to determine the path',
            'array'
        ],
        'templatePathAndFilenamePattern' => [
            '@templateRoot/@subpackage/@controller/@action.@format',
            'File pattern for resolving the template file. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@action", "@format"',
            'string'
        ],
        'partialPathAndFilenamePattern' => [
            '@partialRoot/@subpackage/@partial.@format',
            'Directory pattern for global partials. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@partial", "@format"',
            'string'
        ],
        'layoutPathAndFilenamePattern' => [
            '@layoutRoot/@layout.@format',
            'File pattern for resolving the layout. Following placeholders are supported: "@templateRoot",  "@partialRoot", "@layoutRoot", "@subpackage", "@layout", "@format"',
            'string'
        ],
        'templatePathAndFilename' => [
            null,
            'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern',
            'string'
        ],
        'layoutPathAndFilename' => [
            null,
            'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern',
            'string'
        ]
    ];

    /**
     * The configuration options of this view
     *
     * @see $supportedOptions
     *
     * @var array
     */
    protected $options = [];

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Set default options based on the supportedOptions provided
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        $this->validateOptions($options);
        $this->setOptions($options);

        $context = new RenderingContext($this, $this->options);
        $context->setControllerName('Default');
        $context->setControllerAction('Default');
        $this->setRenderingContext($context);
    }

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
        if ($this->getRenderingContext() instanceof RenderingContext) {
            $this->getRenderingContext()->setControllerContext($controllerContext);
        }


        $paths = $this->getTemplatePaths();
        $request = $controllerContext->getRequest();
        $paths->setFormat($request->getFormat());

        if (!$request instanceof ActionRequest) {
            return;
        }

        if ($paths->getTemplateRootPaths() === [] && $paths->getLayoutRootPaths() === [] && $paths->getPartialRootPaths() === []) {
            $paths->fillDefaultsByPackageName($request->getControllerPackageKey());
        }
        $this->baseRenderingContext->setControllerName(str_replace('\\', '/', $request->getControllerName()));
        $this->baseRenderingContext->setControllerAction($request->getControllerActionName());
    }

    /**
     * @param ControllerContext $controllerContext
     * @return boolean
     */
    public function canRender(ControllerContext $controllerContext)
    {
        return true;
    }

    /**
     * Renders a given section.
     *
     * @param string $sectionName Name of section to render
     * @param array $variables The variables to use
     * @param boolean $ignoreUnknown Ignore an unknown section and just return an empty string
     * @return string rendered template for the section
     * @throws InvalidSectionException
     */
    public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false)
    {
        $renderingContext = $this->getCurrentRenderingContext();

        if ($renderingContext === null) {
            return $this->renderStandaloneSection($sectionName, $variables, $ignoreUnknown);
        }

        return parent::renderSection($sectionName, $variables, $ignoreUnknown);
    }

    /**
     * Renders a section on its own, i.e. without the a surrounding template.
     *
     * In this case, we just emulate that a surrounding (empty) layout exists,
     * and trigger the normal rendering flow then.
     *
     * @param string $sectionName Name of section to render
     * @param array $variables The variables to use
     * @param boolean $ignoreUnknown Ignore an unknown section and just return an empty string
     * @return string rendered template for the section
     */
    protected function renderStandaloneSection($sectionName, array $variables = [], $ignoreUnknown = false)
    {
        $controllerName = $this->baseRenderingContext->getControllerName();
        $templateParser = $this->baseRenderingContext->getTemplateParser();
        $templatePaths = $this->baseRenderingContext->getTemplatePaths();
        $actionName = $this->baseRenderingContext->getControllerAction();
        $actionName = ucfirst($actionName);

        // Note this is (unfortunately) needed to initialize the template
        $templatePaths->getTemplateSource($controllerName, $actionName);
        $templateIdentifier = $templatePaths->getTemplateIdentifier($controllerName, $actionName);
        $parsedTemplate = $templateParser->getOrParseAndStoreTemplate(
            $templateIdentifier,
            function ($parent, TemplatePaths $paths) use ($controllerName, $actionName) {
                return $paths->getTemplateSource($controllerName, $actionName);
            }
        );
        $parsedTemplate->addCompiledNamespaces($this->baseRenderingContext);

        $this->startRendering(self::RENDERING_LAYOUT, $parsedTemplate, $this->baseRenderingContext);
        $output = $this->renderSection($sectionName, $variables, $ignoreUnknown);
        $this->stopRendering();

        return $output;
    }

    /**
     * Validate options given to this view.
     *
     * @param array $options
     * @throws Exception
     */
    protected function validateOptions(array $options)
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new Exception(sprintf('The view options "%s" you\'re trying to set don\'t exist in class "%s".', implode(',', array_keys($unsupportedOptions)), get_class($this)), 1359625876);
        }

        // check for required options being set
        array_walk(
            $this->supportedOptions,
            function ($supportedOptionData, $supportedOptionName, $options) {
                if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
                    throw new Exception('Required view option not set: ' . $supportedOptionName, 1359625876);
                }
            },
            $options
        );
    }

    /**
     * Merges the given options with the default values
     * and sets the resulting options in this object.
     *
     * @param array $options
     */
    protected function setOptions(array $options)
    {
        $this->options = array_merge(
            array_map(
                function ($value) {
                    return $value[0];
                },
                $this->supportedOptions
            ),
            $options
        );
    }

    /**
     * Get a specific option of this View
     *
     * @param string $optionName
     * @return mixed
     * @throws \TYPO3\Flow\Mvc\Exception
     */
    public function getOption($optionName)
    {
        if (!array_key_exists($optionName, $this->supportedOptions)) {
            throw new \TYPO3\Flow\Mvc\Exception(sprintf('The view option "%s" you\'re trying to get doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
        }

        return $this->options[$optionName];
    }

    /**
     * Set a specific option of this View
     *
     * @param string $optionName
     * @param mixed $value
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception
     */
    public function setOption($optionName, $value)
    {
        if (!array_key_exists($optionName, $this->supportedOptions)) {
            throw new \TYPO3\Flow\Mvc\Exception(sprintf('The view option "%s" you\'re trying to set doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
        }

        $this->options[$optionName] = $value;
        if ($this->baseRenderingContext instanceof RenderingContext) {
            $this->baseRenderingContext->setOption($optionName, $value);
        }
    }
}
