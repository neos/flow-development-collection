<?php
namespace Neos\FluidAdaptor\Core\Widget\Exception;

/*
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Neos\FluidAdaptor\Core\Widget;

/**
 * An exception if no widget context could be found inside the AjaxWidgetContextHolder.
 */
class WidgetContextNotFoundException extends Widget\Exception
{
}
