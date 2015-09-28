<?php
namespace TYPO3\Flow\Http\Component;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The HTTP component chain
 *
 * The chain is a HTTP component itself and handles all the configured components until one
 * component sets the "cancelled" flag.
 */
class ComponentChain implements ComponentInterface
{
    /**
     * Configurable options of the component chain, it mainly contains the "components" to handle
     *
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Handle the configured components in the order of the chain
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        if (!isset($this->options['components'])) {
            return;
        }
        /** @var ComponentInterface $component */
        foreach ($this->options['components'] as $component) {
            if ($component === null) {
                continue;
            }
            $component->handle($componentContext);
            if ($componentContext->getParameter('TYPO3\Flow\Http\Component\ComponentChain', 'cancel') === true) {
                $componentContext->setParameter('TYPO3\Flow\Http\Component\ComponentChain', 'cancel', null);
                return;
            }
        }
    }
}
