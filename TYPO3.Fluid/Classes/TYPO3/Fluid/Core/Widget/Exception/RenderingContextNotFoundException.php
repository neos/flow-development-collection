<?php
namespace TYPO3\Fluid\Core\Widget\Exception;

/*
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Fluid\Core\Widget;

/**
 * An exception if no widget context could be found inside the AjaxWidgetContextHolder.
 */
class RenderingContextNotFoundException extends Widget\Exception
{
}
