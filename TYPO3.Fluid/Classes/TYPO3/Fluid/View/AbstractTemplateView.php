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

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\View\AbstractView;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\Fluid\Core\Parser\Configuration;
use TYPO3\Fluid\Core\Parser\Interceptor\Escape as EscapeInterceptor;
use TYPO3\Fluid\Core\Parser\Interceptor\Resource as ResourceInterceptor;
use TYPO3\Fluid\Core\Parser\ParsedTemplateInterface;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\Parser\TemplateParser;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Fluid\View\Exception\InvalidSectionException;

/**
 * Abstract Fluid Template View.
 *
 * Contains the fundamental methods which any Fluid based template view needs.
 */
abstract class AbstractTemplateView extends AbstractView
{
    /**
     * Constants defining possible rendering types
     */
    const RENDERING_TEMPLATE = 1;
    const RENDERING_PARTIAL = 2;
    const RENDERING_LAYOUT = 3;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var TemplateParser
     */
    protected $templateParser;

    /**
     * @var TemplateCompiler
     */
    protected $templateCompiler;

    /**
     * The initial rendering context for this template view.
     * Due to the rendering stack, another rendering context might be active
     * at certain points while rendering the template.
     *
     * @var RenderingContextInterface
     */
    protected $baseRenderingContext;

    /**
     * Stack containing the current rendering type, the current rendering context, and the current parsed template
     * Do not manipulate directly, instead use the methods"getCurrent*()", "startRendering(...)" and "stopRendering()"
     *
     * @var array
     */
    protected $renderingStack = array();

    /**
     * Partial Name -> Partial Identifier cache.
     * This is a performance optimization, effective when rendering a
     * single partial many times.
     *
     * @var array
     */
    protected $partialIdentifierCache = array();

    /**
     * Injects the Object Manager
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Inject the Template Parser
     *
     * @param TemplateParser $templateParser The template parser
     * @return void
     */
    public function injectTemplateParser(TemplateParser $templateParser)
    {
        $this->templateParser = $templateParser;
    }

    /**
     * @param TemplateCompiler $templateCompiler
     * @return void
     */
    public function injectTemplateCompiler(TemplateCompiler $templateCompiler)
    {
        $this->templateCompiler = $templateCompiler;
    }

    /**
     * Injects a fresh rendering context
     *
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        $this->baseRenderingContext = $renderingContext;
        $this->baseRenderingContext->getViewHelperVariableContainer()->setView($this);
        $this->controllerContext = $renderingContext->getControllerContext();
    }

    /**
     * Sets the current controller context
     *
     * @param ControllerContext $controllerContext Controller context which is available inside the view
     * @return void
     * @api
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    //PLACEHOLDER
    // Here, the backporter can insert the initializeView method, which is needed for Fluid v4.

    /**
     * Assign a value to the variable container.
     *
     * @param string $key The key of a view variable to set
     * @param mixed $value The value of the view variable
     * @return \TYPO3\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
     * @api
     */
    public function assign($key, $value)
    {
        $templateVariableContainer = $this->baseRenderingContext->getTemplateVariableContainer();
        if ($templateVariableContainer->exists($key)) {
            $templateVariableContainer->remove($key);
        }
        $templateVariableContainer->add($key, $value);
        return $this;
    }

    /**
     * Assigns multiple values to the JSON output.
     * However, only the key "value" is accepted.
     *
     * @param array $values Keys and values - only a value with key "value" is considered
     * @return \TYPO3\Fluid\View\AbstractTemplateView the instance of this view to allow chaining
     * @api
     */
    public function assignMultiple(array $values)
    {
        $templateVariableContainer = $this->baseRenderingContext->getTemplateVariableContainer();
        foreach ($values as $key => $value) {
            if ($templateVariableContainer->exists($key)) {
                $templateVariableContainer->remove($key);
            }
            $templateVariableContainer->add($key, $value);
        }
        return $this;
    }

    /**
     * Loads the template source and render the template.
     * If "layoutName" is set in a PostParseFacet callback, it will render the file with the given layout.
     *
     * @param string $actionName If set, the view of the specified action will be rendered instead. Default is the action specified in the Request object
     * @return string Rendered Template
     * @api
     */
    public function render($actionName = null)
    {
        $this->baseRenderingContext->setControllerContext($this->controllerContext);
        $this->templateParser->setConfiguration($this->buildParserConfiguration());

        $templateIdentifier = $this->getTemplateIdentifier($actionName);
        if ($this->templateCompiler->has($templateIdentifier)) {
            $parsedTemplate = $this->templateCompiler->get($templateIdentifier);
        } else {
            $parsedTemplate = $this->templateParser->parse($this->getTemplateSource($actionName));
            if ($parsedTemplate->isCompilable()) {
                $this->templateCompiler->store($templateIdentifier, $parsedTemplate);
            }
        }

        if ($parsedTemplate->hasLayout()) {
            $layoutName = $parsedTemplate->getLayoutName($this->baseRenderingContext);
            $layoutIdentifier = $this->getLayoutIdentifier($layoutName);
            if ($this->templateCompiler->has($layoutIdentifier)) {
                $parsedLayout = $this->templateCompiler->get($layoutIdentifier);
            } else {
                $parsedLayout = $this->templateParser->parse($this->getLayoutSource($layoutName));
                if ($parsedLayout->isCompilable()) {
                    $this->templateCompiler->store($layoutIdentifier, $parsedLayout);
                }
            }
            $this->startRendering(self::RENDERING_LAYOUT, $parsedTemplate, $this->baseRenderingContext);
            $output = $parsedLayout->render($this->baseRenderingContext);
            $this->stopRendering();
        } else {
            $this->startRendering(self::RENDERING_TEMPLATE, $parsedTemplate, $this->baseRenderingContext);
            $output = $parsedTemplate->render($this->baseRenderingContext);
            $this->stopRendering();
        }

        return $output;
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
    public function renderSection($sectionName, array $variables = null, $ignoreUnknown = false)
    {
        $renderingContext = $this->getCurrentRenderingContext();

        if ($renderingContext === null) {
            return $this->renderStandaloneSection($sectionName, $variables, $ignoreUnknown);
        }

        if ($this->getCurrentRenderingType() === self::RENDERING_LAYOUT) {
            // in case we render a layout right now, we will render a section inside a TEMPLATE.
            $renderingTypeOnNextLevel = self::RENDERING_TEMPLATE;
        } else {
            /** @var $variableContainer TemplateVariableContainer **/
            $variableContainer = $this->objectManager->get(\TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer::class, $variables);
            $renderingContext = clone $renderingContext;
            $renderingContext->injectTemplateVariableContainer($variableContainer);
            $renderingTypeOnNextLevel = $this->getCurrentRenderingType();
        }

        $parsedTemplate = $this->getCurrentParsedTemplate();

        if ($parsedTemplate->isCompiled()) {
            $methodNameOfSection = 'section_' . sha1($sectionName);
            if ($ignoreUnknown && !method_exists($parsedTemplate, $methodNameOfSection)) {
                return '';
            }
            $this->startRendering($renderingTypeOnNextLevel, $parsedTemplate, $renderingContext);
            $output = $parsedTemplate->$methodNameOfSection($renderingContext);
            $this->stopRendering();
        } else {
            $sections = $parsedTemplate->getVariableContainer()->get('sections');
            if (!array_key_exists($sectionName, $sections)) {
                $controllerObjectName = $this->controllerContext->getRequest()->getControllerObjectName();
                if ($ignoreUnknown) {
                    return '';
                } else {
                    throw new InvalidSectionException(sprintf('Could not render unknown section "%s" in %s used by %s.', $sectionName, get_class($this), $controllerObjectName), 1227108982);
                }
            }
            /** @var $section ViewHelperNode */
            $section = $sections[$sectionName];

            $renderingContext->getViewHelperVariableContainer()->add(\TYPO3\Fluid\ViewHelpers\SectionViewHelper::class, 'isCurrentlyRenderingSection', 'TRUE');

            $this->startRendering($renderingTypeOnNextLevel, $parsedTemplate, $renderingContext);
            $output = $section->evaluate($renderingContext);
            $this->stopRendering();
        }

        return $output;
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
    protected function renderStandaloneSection($sectionName, array $variables = null, $ignoreUnknown = false)
    {
        $templateIdentifier = $this->getTemplateIdentifier();
        if ($this->templateCompiler->has($templateIdentifier)) {
            $parsedTemplate = $this->templateCompiler->get($templateIdentifier);
        } else {
            $this->templateParser->setConfiguration($this->buildParserConfiguration());
            $parsedTemplate = $this->templateParser->parse($this->getTemplateSource());
            if ($parsedTemplate->isCompilable()) {
                $this->templateCompiler->store($templateIdentifier, $parsedTemplate);
            }
        }

        $this->baseRenderingContext->setControllerContext($this->controllerContext);
        $this->startRendering(self::RENDERING_LAYOUT, $parsedTemplate, $this->baseRenderingContext);
        $output = $this->renderSection($sectionName, $variables, $ignoreUnknown);
        $this->stopRendering();
        return $output;
    }

    /**
     * Renders a partial.
     *
     * @param string $partialName
     * @param string $sectionName
     * @param array $variables
     * @return string
     */
    public function renderPartial($partialName, $sectionName, array $variables)
    {
        if (!isset($this->partialIdentifierCache[$partialName])) {
            $this->partialIdentifierCache[$partialName] = $this->getPartialIdentifier($partialName);
        }
        $partialIdentifier = $this->partialIdentifierCache[$partialName];

        if ($this->templateCompiler->has($partialIdentifier)) {
            $parsedPartial = $this->templateCompiler->get($partialIdentifier);
        } else {
            $this->templateParser->setConfiguration($this->buildParserConfiguration());
            $parsedPartial = $this->templateParser->parse($this->getPartialSource($partialName));
            if ($parsedPartial->isCompilable()) {
                $this->templateCompiler->store($partialIdentifier, $parsedPartial);
            }
        }

        /** @var $variableContainer TemplateVariableContainer **/
        $variableContainer = $this->objectManager->get(\TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer::class, $variables);
        $renderingContext = clone $this->getCurrentRenderingContext();
        $renderingContext->injectTemplateVariableContainer($variableContainer);

        $this->startRendering(self::RENDERING_PARTIAL, $parsedPartial, $renderingContext);
        if ($sectionName !== null) {
            $output = $this->renderSection($sectionName, $variables);
        } else {
            $output = $parsedPartial->render($renderingContext);
        }
        $this->stopRendering();

        return $output;
    }

    /**
     * Returns a unique identifier for the resolved template file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $actionName Name of the action. If NULL, will be taken from request.
     * @return string template identifier
     */
    abstract protected function getTemplateIdentifier($actionName = null);

    /**
     * Resolve the template path and filename for the given action. If $actionName
     * is NULL, looks into the current request.
     *
     * @param string $actionName Name of the action. If NULL, will be taken from request.
     * @return string Full path to template
     * @throws \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException in case the template was not found
     */
    abstract protected function getTemplateSource($actionName = null);

    /**
     * Returns a unique identifier for the resolved layout file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $layoutName The name of the layout
     * @return string layout identifier
     */
    abstract protected function getLayoutIdentifier($layoutName = 'Default');

    /**
     * Resolve the path and file name of the layout file, based on
     * $this->layoutPathAndFilename and $this->layoutPathAndFilenamePattern.
     *
     * In case a layout has already been set with setLayoutPathAndFilename(),
     * this method returns that path, otherwise a path and filename will be
     * resolved using the layoutPathAndFilenamePattern.
     *
     * @param string $layoutName Name of the layout to use. If none given, use "Default"
     * @return string Path and filename of layout file
     * @throws \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    abstract protected function getLayoutSource($layoutName = 'Default');

    /**
     * Returns a unique identifier for the resolved partial file.
     * This identifier is based on the template path and last modification date
     *
     * @param string $partialName The name of the partial
     * @return string partial identifier
     */
    abstract protected function getPartialIdentifier($partialName);

    /**
     * Figures out which partial to use.
     *
     * @param string $partialName The name of the partial
     * @return string the full path which should be used. The path definitely exists.
     * @throws \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    abstract protected function getPartialSource($partialName);

    /**
     * Build parser configuration
     *
     * @return Configuration
     */
    protected function buildParserConfiguration()
    {
        /** @var Configuration $parserConfiguration */
        $parserConfiguration = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\Configuration::class);

        /** @var EscapeInterceptor $escapeInterceptor */
        $escapeInterceptor = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\Interceptor\Escape::class);
        $parserConfiguration->addEscapingInterceptor($escapeInterceptor);

        $request = $this->controllerContext->getRequest();
        if ($request instanceof ActionRequest && in_array($request->getFormat(), array('html', null))) {
            /** @var ResourceInterceptor $resourceInterceptor */
            $resourceInterceptor = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\Interceptor\Resource::class);
            $parserConfiguration->addInterceptor($resourceInterceptor);
        }

        return $parserConfiguration;
    }

    /**
     * Start a new nested rendering. Pushes the given information onto the $renderingStack.
     *
     * @param integer $type one of the RENDERING_* constants
     * @param ParsedTemplateInterface $parsedTemplate
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    protected function startRendering($type, ParsedTemplateInterface $parsedTemplate, RenderingContextInterface $renderingContext)
    {
        array_push($this->renderingStack, array('type' => $type, 'parsedTemplate' => $parsedTemplate, 'renderingContext' => $renderingContext));
    }

    /**
     * Stops the current rendering. Removes one element from the $renderingStack. Make sure to always call this
     * method pair-wise with startRendering().
     *
     * @return void
     */
    protected function stopRendering()
    {
        array_pop($this->renderingStack);
    }

    /**
     * Get the current rendering type.
     *
     * @return integer one of RENDERING_* constants
     */
    protected function getCurrentRenderingType()
    {
        $currentRendering = end($this->renderingStack);
        return $currentRendering['type'];
    }

    /**
     * Get the parsed template which is currently being rendered.
     *
     * @return ParsedTemplateInterface
     */
    protected function getCurrentParsedTemplate()
    {
        $currentRendering = end($this->renderingStack);
        return $currentRendering['parsedTemplate'];
    }

    /**
     * Get the rendering context which is currently used.
     *
     * @return RenderingContextInterface
     */
    protected function getCurrentRenderingContext()
    {
        $currentRendering = end($this->renderingStack);
        return $currentRendering['renderingContext'];
    }

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * By default we assume that the view implementation can handle all kinds of
     * contexts. Override this method if that is not the case.
     *
     * @param ControllerContext $controllerContext Controller context which is available inside the view
     * @return boolean TRUE if the view has something useful to display, otherwise FALSE
     */
    public function canRender(ControllerContext $controllerContext)
    {
        return true;
    }
}
