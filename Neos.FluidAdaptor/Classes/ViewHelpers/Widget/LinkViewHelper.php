<?php
namespace Neos\FluidAdaptor\ViewHelpers\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper;
use Neos\FluidAdaptor\Core\Widget\Exception\WidgetContextNotFoundException;
use Neos\FluidAdaptor\Core\Widget\WidgetContext;

/**
 * widget.link ViewHelper
 * This ViewHelper can be used inside widget templates in order to render links pointing to widget actions
 *
 * = Examples =
 *
 * <code>
 * <f:widget.link action="widgetAction" arguments="{foo: 'bar'}">some link</f:widget.link>
 * </code>
 * <output>
 *  <a href="--widget[@action]=widgetAction">some link</a>
 *  (depending on routing setup and current widget)
 * </output>
 *
 * @api
 */
class LinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

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
     * @param string $section The anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html"
     * @param boolean $ajax TRUE if the URI should be to an AJAX widget, FALSE otherwise.
     * @param boolean $includeWidgetContext TRUE if the URI should contain the serialized widget context (only useful for stateless AJAX widgets)
     * @return string The rendered link
     * @throws ViewHelper\Exception if $action argument is not specified and $ajax is FALSE
     * @api
     */
    public function render($action = null, $arguments = array(), $section = '', $format = '', $ajax = false, $includeWidgetContext = false)
    {
        if ($ajax === true) {
            $uri = $this->getAjaxUri();
        } else {
            if ($action === null) {
                throw new ViewHelper\Exception('You have to specify the target action when creating a widget URI with the widget.link ViewHelper', 1357648227);
            }
            $uri = $this->getWidgetUri();
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }

    /**
     * Get the URI for an AJAX Request.
     *
     * @return string the AJAX URI
     * @throws WidgetContextNotFoundException
     */
    protected function getAjaxUri()
    {
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];

        if ($action === null) {
            $action = $this->controllerContext->getRequest()->getControllerActionName();
        }
        $arguments['@action'] = $action;
        if (strlen($this->arguments['format']) > 0) {
            $arguments['@format'] = $this->arguments['format'];
        }
        /** @var $widgetContext WidgetContext */
        $widgetContext = $this->controllerContext->getRequest()->getInternalArgument('__widgetContext');
        if ($widgetContext === null) {
            throw new WidgetContextNotFoundException('Widget context not found in <f:widget.link>', 1307450686);
        }
        if ($this->arguments['includeWidgetContext'] === true) {
            $serializedWidgetContext = serialize($widgetContext);
            $arguments['__widgetContext'] = $this->hashService->appendHmac($serializedWidgetContext);
        } else {
            $arguments['__widgetId'] = $widgetContext->getAjaxWidgetIdentifier();
        }
        return '?' . http_build_query($arguments, null, '&');
    }

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @return string the Widget URI
     * @throws ViewHelper\Exception
     * @todo argumentsToBeExcludedFromQueryString does not work yet, needs to be fixed.
     */
    protected function getWidgetUri()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();

        $argumentsToBeExcludedFromQueryString = array(
            '@package',
            '@subpackage',
            '@controller'
        );

        $uriBuilder
            ->reset()
            ->setSection($this->arguments['section'])
            ->setCreateAbsoluteUri(true)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setFormat($this->arguments['format']);
        try {
            $uri = $uriBuilder->uriFor($this->arguments['action'], $this->arguments['arguments'], '', '', '');
        } catch (\Exception $exception) {
            throw new ViewHelper\Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $uri;
    }
}
