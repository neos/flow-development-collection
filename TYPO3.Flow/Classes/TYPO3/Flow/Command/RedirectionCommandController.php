<?php
namespace TYPO3\Flow\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Redirection\Redirection;
use TYPO3\Flow\Http\Redirection\Storage\RedirectionStorageInterface;

/**
 * Command controller for tasks related to redirects
 *
 * @Flow\Scope("singleton")
 */
class RedirectionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var RedirectionStorageInterface
     */
    protected $redirectionStorage;

    /**
     * List all redirections
     *
     * This command displays a list of all currently registered redirections.
     *
     * @return void
     */
    public function listCommand()
    {
        $redirections = $this->redirectionStorage->getAll();

        $numberOfRedirects = count($redirections);
        if ($numberOfRedirects < 1) {
            $this->outputLine('There are no registered redirections');
            $this->quit();
        }
        $this->outputLine('Currently registered redirections (%d):', array($numberOfRedirects));
        $this->outputLine();
        $this->outputLine('Source Path => Target Path                                           Status Code');
        $this->outputLine(str_repeat('-', 80));
        /** @var $redirection Redirection */
        foreach ($redirections as $redirection) {
            $this->outputLine('%s %d', array(str_pad($redirection->getSourceUriPath(), 76), $redirection->getStatusCode()));
            $this->outputLine('  => %s', array($redirection->getTargetUriPath()));
        }
    }

    /**
     * Removes a redirection
     *
     * This command deletes a redirection from the RedirectionRepository
     *
     * @param string $sourcePath The source URI path of the redirection to remove, as given by redirect:list
     * @return void
     */
    public function removeCommand($sourcePath)
    {
        $redirection = $this->redirectionStorage->getOneBySourceUriPath($sourcePath);
        if ($redirection === null) {
            $this->outputLine('There is no redirection with the source URI path "%s"', array($sourcePath));
            $this->quit(1);
        }
        $this->redirectionStorage->removeOneBySourceUriPath($sourcePath);
        $this->outputLine('Removed redirection with the source URI path "%s"', array($sourcePath));
    }

    /**
     * Removes all redirections
     *
     * This command deletes all redirections from the RedirectionRepository
     *
     * @return void
     */
    public function removeAllCommand()
    {
        $redirections = $this->redirectionStorage->getAll();
        $numberOfRedirects = count($redirections);
        if ($numberOfRedirects < 1) {
            $this->outputLine('There are no registered redirections');
            $this->quit();
        }
        $this->redirectionStorage->removeAll();
        if ($numberOfRedirects === 1) {
            $this->outputLine('Removed one redirection');
        } else {
            $this->outputLine('Removed %d redirections', array($numberOfRedirects));
        }
    }

    /**
     * Adds a redirection
     *
     * This command adds a new redirection to the RedirectionRepository using the RedirectionService
     *
     * @param string $sourcePath The relative URI path that should trigger the redirect
     * @param string $targetPath The relative URI path that should be redirected to
     * @param integer $statusCode The status code of the redirect header
     * @return void
     */
    public function addCommand($sourcePath, $targetPath, $statusCode = 301)
    {
        $this->redirectionStorage->addRedirection($sourcePath, $targetPath, $statusCode);
        $this->outputLine('New redirection created!');
    }
}
