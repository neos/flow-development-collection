<?php
namespace Neos\FluidAdaptor\ViewHelpers\Link;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper;

/**
 * A view helper for creating links to actions.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:link.action>some link</f:link.action>
 * </code>
 * <output>
 * <a href="currentpackage/currentcontroller">some link</a>
 * (depending on routing setup and current package/controller/action)
 * </output>
 *
 * <code title="Additional arguments">
 * <f:link.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:link.action>
 * </code>
 * <output>
 * <a href="mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2">some link</a>
 * (depending on routing setup)
 * </output>
 *
 * @api
 */
class ActionViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    /**
     * Render the link.
     *
     * @param string $action Target action
     * @param array $arguments Arguments
     * @param string $controller Target controller. If NULL current controllerName is used
     * @param string $package Target package. if NULL current package is used
     * @param string $subpackage Target subpackage. if NULL current subpackage is used
     * @param string $section The anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html"
     * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param boolean $useParentRequest If set, the parent Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care
     * @param boolean $absolute By default this ViewHelper renders links with absolute URIs. If this is FALSE, a relative URI is created instead
     * @param boolean $useMainRequest If set, the main Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care
     * @return string The rendered link
     * @throws ViewHelper\Exception
     * @api
     */
    public function render($action, $arguments = array(), $controller = null, $package = null, $subpackage = null, $section = '', $format = '', array $additionalParams = array(), $addQueryString = false, array $argumentsToBeExcludedFromQueryString = array(), $useParentRequest = false, $absolute = true, $useMainRequest = false)
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        if ($useParentRequest) {
            $request = $this->controllerContext->getRequest();
            if ($request->isMainRequest()) {
                throw new ViewHelper\Exception('You can\'t use the parent Request, you are already in the MainRequest.', 1360163536);
            }
            $uriBuilder = clone $uriBuilder;
            $uriBuilder->setRequest($request->getParentRequest());
        } elseif ($useMainRequest === true) {
            $request = $this->controllerContext->getRequest();
            if (!$request->isMainRequest()) {
                $uriBuilder = clone $uriBuilder;
                $uriBuilder->setRequest($request->getMainRequest());
            }
        }

        $uriBuilder
            ->reset()
            ->setSection($section)
            ->setCreateAbsoluteUri($absolute)
            ->setArguments($additionalParams)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setFormat($format);
        try {
            $uri = $uriBuilder->uriFor($action, $arguments, $controller, $package, $subpackage);
        } catch (\Exception $exception) {
            throw new ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
