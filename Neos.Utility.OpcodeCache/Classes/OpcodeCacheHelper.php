<?php

namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.OpCodeCache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This class contains a helper to clear PHP Opcode Caches, auto-detecting the current opcache system in use.
 *
 * It has been inspired by the corresponding functionality in TYPO3 CMS (OpcodeCacheUtility.php), especially the cache-
 * invalidate functions.
 */
abstract class OpcodeCacheHelper
{
    /**
     * Contains callback functions for all active Opcode caches which can be used to flush a file.
     *
     * @var array<\Closure>
     */
    protected static $clearCacheCallbacks = [];

    /**
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Initialize the ClearCache-Callbacks
     *
     * @return void
     */
    protected static function initialize()
    {
        self::$clearCacheCallbacks = [];

        // OpCache - http://php.net/manual/en/book.opcache.php
        if (extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1') {
            self::$clearCacheCallbacks[] = function ($absolutePathAndFilename) {
                if ($absolutePathAndFilename !== null && function_exists('opcache_invalidate')) {
                    opcache_invalidate($absolutePathAndFilename);
                } else {
                    opcache_reset();
                }
            };
        }

        self::$initialized = true;
    }

    /**
     * Clear a PHP file from all active cache files. Also supports to flush the cache completely, if called without parameter.
     *
     * @param string $absolutePathAndFilename Absolute path towards the PHP file to clear.
     * @return void
     */
    public static function clearAllActive(?string $absolutePathAndFilename = null)
    {
        if (self::$initialized === false) {
            self::initialize();
        }
        foreach (self::$clearCacheCallbacks as $clearCacheCallback) {
            $clearCacheCallback($absolutePathAndFilename);
        }
    }
}
