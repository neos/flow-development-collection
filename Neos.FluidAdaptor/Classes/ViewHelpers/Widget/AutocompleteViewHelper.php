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
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;

/**
 * Usage:
 * <f:input id="name" ... />
 * <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author">
 *
 * Make sure to include jQuery and jQuery UI in the HTML, like that:
 *    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
 *    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
 *    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/themes/base/jquery-ui.css" type="text/css" media="all" />
 *    <link rel="stylesheet" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" type="text/css" media="all" />
 *
 * @api
 */
class AutocompleteViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var bool
     */
    protected $ajaxWidget = true;

    /**
     * @Flow\Inject
     * @var Controller\AutocompleteController
     */
    protected $controller;

    /**
     *
     * @param QueryResultInterface $objects
     * @param string $for
     * @param string $searchProperty
     * @param array $configuration
     * @return string
     */
    public function render(QueryResultInterface $objects, $for, $searchProperty, array $configuration = array('limit' => 10))
    {
        return $this->initiateSubRequest();
    }
}
