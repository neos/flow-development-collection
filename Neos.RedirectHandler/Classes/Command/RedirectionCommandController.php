<?php
namespace Neos\RedirectHandler\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\Service\SettingsService;
use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;

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
     * @Flow\Inject
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * List all redirections
     *
     * This command displays a list of all currently registered redirections.
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function listCommand($host = null)
    {
        $redirections = $this->redirectionStorage->getAll($host);

        $numberOfRedirects = count($redirections);
        if ($numberOfRedirects < 1) {
            $this->outputLine('There are no registered redirections');
            $this->quit();
        }
        $this->outputLine('Currently registered redirections (%d):', [$numberOfRedirects]);
        $this->outputLine();
        $data = [];
        $headers = [
            'status' => 'Status',
            'host' => 'Host Pattern',
            'source' => 'Source URI',
            'target' => 'Target URI'
        ];
        /** @var $redirection \Neos\RedirectHandler\Redirection */
        foreach ($redirections as $redirection) {
            $data[] = [
                'status' => $redirection->getStatusCode(),
                'host' => $redirection->getHostPattern(),
                'source' => $redirection->getSourceUriPath(),
                'target' => $redirection->getTargetUriPath()
            ];
        }
        $this->output->outputTable($data, $headers);
    }

    /**
     * Removes a redirection
     *
     * This command deletes a redirection from the RedirectionRepository
     *
     * @param string $sourcePath The source URI path of the redirection to remove, as given by redirect:list
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeCommand($sourcePath, $host = null)
    {
        $redirection = $this->redirectionStorage->getOneBySourceUriPathAndHost($sourcePath, $host);
        if ($redirection === null) {
            $this->outputLine('There is no redirection with the source URI path "%s"', [$sourcePath]);
            $this->quit(1);
        }
        $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourcePath, $host);
        $this->outputLine('Removed redirection with the source URI path "%s"', [$sourcePath]);
    }

    /**
     * Removes all redirections
     *
     * This command deletes all redirections from the RedirectionRepository
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeAllCommand($host = null)
    {
        $this->redirectionStorage->removeAll($host);
        if ($host === null) {
            $this->outputLine('Removed all redirections');
        } else {
            $this->outputLine('Removed all redirections for host "%s"', [$host]);
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
     * @param string $hostPattern Host pattern to match the redirect
     * @return void
     */
    public function addCommand($sourcePath, $targetPath, $statusCode = null, $hostPattern)
    {
        $statusCode = $statusCode ?: $this->settingsService->getRedirectStatusCode();
        $this->redirectionStorage->addRedirection($sourcePath, $targetPath, $statusCode, [$hostPattern]);
        $this->outputLine('New redirection created!');
    }
}
