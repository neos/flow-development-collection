<?php
namespace TYPO3\Flow\Http\Redirection\Storage;
/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Http\Redirection\Redirection as RedirectionDto;

/**
 * Redirection Storage Interface
 */
interface RedirectionStorageInterface
{
    /**
     * Returns one redirection for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @return RedirectionDto or NULL if no redirection exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPath($sourceUriPath);

    /**
     * Returns all registered redirection records
     *
     * @return \Generator<RedirectionDto>
     * @api
     */
    public function getAll();

    /**
     * Removes a redirection for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @return void
     * @api
     */
    public function removeOneBySourceUriPath($sourceUriPath);

    /**
     * Removes all registered redirection records
     *
     * @return void
     * @api
     */
    public function removeAll();

    /**
     * Adds a redirection to the repository and updates related redirection instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @return Redirection the freshly generated redirection instance
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = 301);
}
