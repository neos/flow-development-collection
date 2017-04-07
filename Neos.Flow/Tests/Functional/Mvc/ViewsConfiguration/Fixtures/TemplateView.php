<?php
namespace Neos\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\View\AbstractView;

/**
 * An empty view - a special case.
 *
 */
final class TemplateView extends AbstractView
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'templateRootPathPattern' => ['@packageResourcesPath/Private/Templates', 'Pattern to be resolved for "@templateRoot" in the other patterns', 'string'],
        'partialRootPathPattern' => ['@packageResourcesPath/Private/Partials', 'Pattern to be resolved for "@partialRoot" in the other patterns', 'string'],
        'layoutRootPathPattern' => ['@packageResourcesPath/Private/Layouts', 'Pattern to be resolved for "@layoutRoot" in the other patterns', 'string'],

        'templateRootPath' => [null, 'Path to the template root. If NULL, then $this->templateRootPathPattern will be used', 'string'],
        'partialRootPath' => [null, 'Path to the partial root. If NULL, then $this->partialRootPathPattern will be used', 'string'],
        'layoutRootPath' => [null, 'Path to the layout root. If NULL, then $this->layoutRootPathPattern will be used', 'string'],

        'templatePathAndFilenamePattern' => ['@templateRoot/@subpackage/@controller/@action.@format', 'File pattern for resolving the template file', 'string'],
        'partialPathAndFilenamePattern' => ['@partialRoot/@subpackage/@partial.@format', 'Directory pattern for global partials. Not part of the public API, should not be changed for now.', 'string'],
        'layoutPathAndFilenamePattern' => ['@layoutRoot/@layout.@format', 'File pattern for resolving the layout', 'string'],

        'templatePathAndFilename' => [null, 'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern', 'string'],
        'layoutPathAndFilename' => [null, 'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern', 'string'],
    ];

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param ControllerContext $controllerContext
     * @return void
     * @api
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param string $key
     * @param mixed $value
     * @return self instance of $this to allow chaining
     * @api
     */
    public function assign($key, $value)
    {
        return $this;
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param array $values
     * @return self instance of $this to allow chaining
     * @api
     */
    public function assignMultiple(array $values)
    {
        return $this;
    }

    /**
     * This view can be used in any case.
     *
     * @param ControllerContext $controllerContext
     * @return boolean TRUE
     * @api
     */
    public function canRender(ControllerContext $controllerContext)
    {
        return true;
    }

    /**
     * Renders the empty view
     *
     * @return string An empty string
     */
    public function render()
    {
        return get_class($this);
    }

    /**
     * A magic call method.
     *
     * Because this empty view is used as a Special Case in situations when no matching
     * view is available, it must be able to handle method calls which originally were
     * directed to another type of view. This magic method should prevent PHP from issuing
     * a fatal error.
     *
     * @param string $methodName Name of the method
     * @param array $arguments Arguments passed to the method
     * @return void
     */
    public function __call($methodName, array $arguments)
    {
    }
}
