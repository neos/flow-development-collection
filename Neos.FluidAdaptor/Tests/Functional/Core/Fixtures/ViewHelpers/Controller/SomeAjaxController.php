<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\ViewHelpers\Controller;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Widget\AbstractWidgetController;

/**
 * Controller of the test AJAX widget
 */
class SomeAjaxController extends AbstractWidgetController
{
    /**
     * The default action which is invoked when the widget is rendered as part of a
     * Fluid template.
     *
     * The template of this action renders an OK string and the URI pointing to the
     * ajaxAction().
     *
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * An action which is supposed to be invoked through AJAX
     *
     * @return string
     */
    public function ajaxAction()
    {
        $options = (isset($this->widgetConfiguration['option1']) ? '"' . $this->widgetConfiguration['option1'] . '"' : '""') . ', ';
        $options .= (isset($this->widgetConfiguration['option2']) ? '"' . $this->widgetConfiguration['option2'] . '"' : '""') . '';
        return sprintf('SomeAjaxController::ajaxAction(%s)', $options);
    }
}
