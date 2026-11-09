<?php

namespace Neos\FluidAdaptor\Core\Fixtures;

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

class TestViewHelper extends AbstractViewHelper
{
    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('param1', 'integer', 'P1 Stuff', true);
        $this->registerArgument('param2', 'array', 'P2 Stuff', true);
        $this->registerArgument('param3', 'string', 'P3 Stuff', false, 'default');
    }

    /**
     * My comments. Bla blubb.
     */
    public function render(): void
    {
    }
}
