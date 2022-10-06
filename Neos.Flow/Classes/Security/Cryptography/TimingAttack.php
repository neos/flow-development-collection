<?php
namespace Neos\Flow\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms as UtilityAlgorithms;

/**
 * Calculates and holds the duration the default cryptography strategy needs
 *
 * @Flow\Scope("singleton")
 */
class TimingAttack
{
    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $cache;

    /**
     * @var float
     * @Flow\InjectConfiguration(path="security.cryptography.timingAttack.buffer")
     */
    protected $buffer;

    /**
     * @var float
     * @Flow\InjectConfiguration(path="security.cryptography.timingAttack.precision")
     */
    protected $precision;

    /**
     * @return float
     */
    public function getCryptographyDuration()
    {
        $duration = $this->cache->get('cryptography_duration');
        if (!$duration) {
            $this->calculateAndCacheCryptographyDuration();
            $duration = 0;
        }

        return $duration;
    }

    public function calculateAndCacheCryptographyDuration()
    {
        $randomPassword = UtilityAlgorithms::generateRandomString(16, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./');
        $start = microtime(true);
        $this->hashService->hashPassword($randomPassword);
        $this->setOptimizedDurationToCache(microtime(true) - $start);
    }

    protected function setOptimizedDurationToCache(float $duration)
    {
        $durationRounded = ceil(($duration + $this->buffer) / $this->precision) * $this->precision;
        $this->cache->set('cryptography_duration', $durationRounded);

        return $durationRounded;
    }
}
