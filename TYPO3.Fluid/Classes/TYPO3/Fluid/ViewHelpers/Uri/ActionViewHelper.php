<?php
namespace TYPO3\Fluid\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper;

/**
 * A view helper for creating URIs to actions.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:uri.action>some link</f:uri.action>
 * </code>
 * <output>
 * currentpackage/currentcontroller
 * (depending on routing setup and current package/controller/action)
 * </output>
 *
 * <code title="Additional arguments">
 * <f:uri.action action="myAction" controller="MyController" package="YourCompanyName.MyPackage" subpackage="YourCompanyName.MySubpackage" arguments="{key1: 'value1', key2: 'value2'}">some link</f:uri.action>
 * </code>
 * <output>
 * mypackage/mycontroller/mysubpackage/myaction?key1=value1&amp;key2=value2
 * (depending on routing setup)
 * </output>
 *
 * @api
 */
class ActionViewHelper extends AbstractViewHelper
{
    /**
     * Render the Uri.
     *
     * @param string $action Target action
     * @param array $arguments Arguments
     * @param string $controller Target controller. If NULL current controllerName is used
     * @param string $package Target package. if NULL current package is used
     * @param string $subpackage Target subpackage. if NULL current subpackage is used
     * @param string $section The anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html"
     * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     * @param boolean $absolute If set, an absolute URI is rendered
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param boolean $useParentRequest If set, the parent Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care
     * @param boolean $useMainRequest If set, the main Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care
     * @return string The rendered link
     * @throws ViewHelper\Exception
     * @api
     */
    public function render($action, array $arguments = array(), $controller = null, $package = null, $subpackage = null, $section = '', $format = '', array $additionalParams = array(), $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = array(), $useParentRequest = false, $useMainRequest = false)
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        if ($useParentRequest === true) {
            $request = $this->controllerContext->getRequest();
            if ($request->isMainRequest()) {
                throw new ViewHelper\Exception('You can\'t use the parent Request, you are already in the MainRequest.', 1360590758);
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
        return $uri;
    }
}
