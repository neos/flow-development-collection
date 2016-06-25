<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * Class ViewHelperResolver
 *
 * Class whose purpose is dedicated to resolving classes which
 * can be used as ViewHelpers and ExpressionNodes in Fluid.
 *
 * In addition to modifying the behavior or the parser when
 * legacy mode is requested, this ViewHelperResolver is also
 * made capable of "mixing" two different ViewHelper namespaces
 * to effectively create aliases for the Fluid core ViewHelpers
 * to be loaded in the (TYPO3|Neos)\Fluid\ViewHelper scope as well.
 *
 * @Flow\Scope("singleton")
 */
class ViewHelperResolver extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Custom merged namespace for CMS Fluid adapter;
     * will look for classes in both namespaces starting
     * from the bottom.
     *
     * @var array
     */
    protected $namespaces = [
        'f' => [
            'TYPO3Fluid\\Fluid\\ViewHelpers',
            'TYPO3\\Fluid\\ViewHelpers'
        ]
    ];

    /**
     * @param string $viewHelperClassName
     * @return \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
     */
    public function createViewHelperInstanceFromClassName($viewHelperClassName)
    {
        return $this->objectManager->get($viewHelperClassName);
    }
}
