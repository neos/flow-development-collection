<?php
namespace Neos\FluidAdaptor\Core\Rendering;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * Interface for rendering contexts that are Flow aware.
 *
 * @see \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
 */
interface FlowAwareRenderingContextInterface
{
    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager();

    /**
     * @return ControllerContext
     */
    public function getControllerContext();
}
