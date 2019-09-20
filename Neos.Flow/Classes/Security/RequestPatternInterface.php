<?php
namespace Neos\Flow\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;

/**
 * Contract for a request pattern.
 *
 */
interface RequestPatternInterface
{
    /**
     * Note: This constructor is commented by intention due to the way PHPs inheritance works.
     * But request pattern implementations relying on custom options should implement it
     *
     * @param array $options Additional configuration options
     * @return void
     */
    // public function __construct(array $options);

    /**
     * Matches an ActionRequest against its set pattern rules
     *
     * @param ActionRequest $request The request that should be matched
     * @return boolean true if the pattern matched, false otherwise
     */
    public function matchRequest(ActionRequest $request);
}
