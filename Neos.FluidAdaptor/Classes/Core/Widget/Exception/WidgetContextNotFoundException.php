<?php
namespace Neos\FluidAdaptor\Core\Widget\Exception;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Widget;

/**
 * An exception if no widget context could be found inside the AjaxWidgetContextHolder.
 */
class WidgetContextNotFoundException extends Widget\Exception
{
}
