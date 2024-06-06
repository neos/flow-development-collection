<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Tests\PhpBench\Core;

use Neos\BuildEssentials\PhpBench\FrameworkEnabledBenchmark;
use Neos\BuildEssentials\TestableFramework;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Testing\RequestHandler\EmptyRequestHandler;
use Neos\Flow\Testing\RequestHandler\RuntimeSequenceInvokingRequestHandler;

/**
 *
 */
class BootstrapBench extends FrameworkEnabledBenchmark
{
    /**
     * How long does constructing the bootstrap take
     *
     * @BeforeMethods("withRootPath")
     * @Revs(5)
     */
    public function benchBootstrapConstruct(): void
    {
        $flowBootstrap = new Bootstrap(TestableFramework::getApplicationContext());
    }

    /**
     * How long does creating a bootstrap and running an (empty) request handler take
     *
     * @BeforeMethods("withRootPath")
     * @Revs(5)
     */
    public function benchBootstrapRunWithoutBootSequence(): void
    {
        $flowBootstrap = new Bootstrap(TestableFramework::getApplicationContext());
        $flowBootstrap->registerRequestHandler(new EmptyRequestHandler());
        $flowBootstrap->setPreselectedRequestHandlerClassName(EmptyRequestHandler::class);
        $flowBootstrap->run();
    }

    /**
     * How long does the runtime boot sequence take
     * Warmup of 1 cycle to trigger compile outside of the measurement
     *
     * @BeforeMethods("withRootPath")
     * @Revs(3)
     * @Warmup(1)
     */
    public function benchBootstrapRunWithRuntimeBootSequence(): void
    {
        $flowBootstrap = new Bootstrap(TestableFramework::getApplicationContext());
        $flowBootstrap->registerRequestHandler(new RuntimeSequenceInvokingRequestHandler($flowBootstrap));
        $flowBootstrap->setPreselectedRequestHandlerClassName(RuntimeSequenceInvokingRequestHandler::class);
        $flowBootstrap->run();
    }
}
