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

use Neos\Flow\Mvc\ActionRequest;
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
        $this->registerArgument('action', 'string', 'Target action', true);
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used', false, null);
        $this->registerArgument('package', 'string', 'Target package. if NULL current package is used', false, null);
        $this->registerArgument('subpackage', 'string', 'Target subpackage. if NULL current subpackage is used', false, null);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html"', false, '');
        $this->registerArgument('additionalParams', 'array', 'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('addQueryString', 'boolean', 'If set, the current query parameters will be kept in the URI', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = true', false, []);
        $this->registerArgument('useParentRequest', 'boolean', 'If set, the parent Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care', false, false);
        $this->registerArgument('absolute', 'boolean', 'By default this ViewHelper renders links with absolute URIs. If this is false, a relative URI is created instead', false, true);
        $this->registerArgument('useMainRequest', 'boolean', 'If set, the main Request will be used instead of the current one. Note: using this argument can be a sign of undesired tight coupling, use with care', false, false);
    }

    /**
     * Render the link.
     *
     * @return string The rendered link
     * @throws ViewHelper\Exception
     * @api
     */
    public function render()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        if ($this->arguments['useParentRequest']) {
            $request = $this->controllerContext->getRequest();
            if ($request->isMainRequest()) {
                throw new ViewHelper\Exception('You can\'t use the parent Request, you are already in the MainRequest.', 1360163536);
            }
            $parentRequest = $request->getParentRequest();
            if (!$parentRequest instanceof ActionRequest) {
                throw new ViewHelper\Exception('The parent requests was unexpectedly empty, probably the current request is broken.', 1565948237);
            }

            $uriBuilder = clone $uriBuilder;
            $uriBuilder->setRequest($parentRequest);
        } elseif ($this->arguments['useMainRequest'] === true) {
            $request = $this->controllerContext->getRequest();
            if (!$request->isMainRequest()) {
                $uriBuilder = clone $uriBuilder;
                $uriBuilder->setRequest($request->getMainRequest());
            }
        }

        $uriBuilder
            ->reset()
            ->setSection($this->arguments['section'])
            ->setCreateAbsoluteUri($this->arguments['absolute'])
            ->setArguments($this->arguments['additionalParams'])
            ->setAddQueryString($this->arguments['addQueryString'])
            ->setArgumentsToBeExcludedFromQueryString($this->arguments['argumentsToBeExcludedFromQueryString'])
            ->setFormat($this->arguments['format']);
        try {
            $uri = $uriBuilder->uriFor($this->arguments['action'], $this->arguments['arguments'], $this->arguments['controller'], $this->arguments['package'], $this->arguments['subpackage']);
        } catch (\Exception $exception) {
            throw new ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
