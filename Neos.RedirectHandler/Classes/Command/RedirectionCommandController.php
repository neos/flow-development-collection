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

use League\Csv\Reader;
use League\Csv\Writer;
use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\RedirectInterface;
use Neos\RedirectHandler\Storage\RedirectStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * Command controller for tasks related to redirects
 *
 * @Flow\Scope("singleton")
 */
class RedirectionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var RedirectStorageInterface
     */
    protected $redirectStorage;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param string $host Filter redirection by the given hostname
     * @param string $match Match source or target URI by a regular expression
     * @return void
     */
    public function listCommand($host = null, $match = null)
    {
        $outputByHost = function ($host) use ($match) {
            $redirects = $this->redirectStorage->getAll($host);
            $this->outputLine();
            if ($host !== null) {
                $this->outputLine('<info>==</info> <b>Redirection for %s</b>', [$host]);
            } else {
                $this->outputLine('<info>==</info> <b>Redirection without host attached</b>', [$host]);
            }
            if ($match !== null) {
                $this->outputLine('   <info>++</info> <b>Show only if source or target URI match <u>%s</u></b>', [$match]);
                sleep(1);
            }
            $this->outputLine();
            /** @var $redirect RedirectInterface */
            foreach ($redirects as $redirect) {
                $outputLine = function ($source, $target, $statusCode) {
                    $this->outputLine('   <comment>></comment> %s <info>=></info> %s <comment>(%d)</comment>', [
                        $source,
                        $target,
                        $statusCode
                    ]);
                };
                $source = $redirect->getSourceUriPath();
                $target = $redirect->getTargetUriPath();
                $statusCode = $redirect->getStatusCode();
                if ($match === null) {
                    $outputLine($source, $target, $statusCode);
                } else {
                    $regexp = sprintf('#%s#', $match);
                    $matches = preg_grep($regexp, [$target, $source]);
                    if (count($matches) > 0) {
                        $replace = "<error>$0</error>";
                        $source = preg_replace($regexp, $replace, $source);
                        $target = preg_replace($regexp, $replace, $target);
                        $outputLine($source, $target, $statusCode);
                    }
                }
            }
        };
        if ($host !== null) {
            $outputByHost($host);
        } else {
            $hosts = $this->redirectStorage->getDistinctHosts();
            if ($hosts !== []) {
                array_map($outputByHost, $hosts);
            }
            $outputByHost($host);
        }
        $this->outputLine();
    }

    /**
     * Save all redirectection to a CSV file
     *
     * @param string $filename CSV file path, if null use standard output
     * @param string $host Optional host pattern
     * @return void
     */
    public function exportCommand($filename = null, $host = null)
    {
        if (!class_exists(Writer::class)) {
            $this->outputWarningForLeagueCsvPackage();
        }
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $redirects = $this->redirectStorage->getAll($host);
        /** @var $redirect RedirectInterface */
        foreach ($redirects as $redirect) {
            $writer->insertOne([
                $redirect->getSourceUriPath(),
                $redirect->getTargetUriPath(),
                $redirect->getStatusCode(),
                $redirect->getHost() ?: '[no host attached]'
            ]);
        }
        if ($filename === null) {
            $writer->output();
        } else {
            file_put_contents($filename, (string)$writer);
        }
    }

    /**
     * Load redirection from a CSV file
     *
     * @param string $filename CSV file path
     * @return void
     */
    public function importCommand($filename)
    {
        $this->outputLine();
        if (!class_exists(Reader::class)) {
            $this->outputWarningForLeagueCsvPackage();
        }
        if (!is_readable($filename)) {
            $this->outputLine('<error>Sorry, but the file "%s" is not readable or does not exist...</error>', [$filename]);
            $this->outputLine();
            $this->sendAndExit(1);
        }
        $this->outputLine('<b>Import redirection from "%s"</b>', [$filename]);
        $this->outputLine();
        $reader = Reader::createFromPath($filename);
        $counter = 0;
        foreach ($reader as $index => $row) {
            $skipped = false;
            list($sourceUriPath, $targetUriPath, $statusCode, $hosts) = $row;
            $hosts = Arrays::trimExplode('|', $hosts);
            $forcePersist = false;
            foreach ($hosts as $key => $host) {
                $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($sourceUriPath, $host);
                $isSame = $this->isSame($sourceUriPath, $targetUriPath, $host, $statusCode, $redirect);
                if ($redirect !== null && $isSame === false) {
                    $this->outputRedirectLine('<info>--</info>', $redirect);
                    $this->redirectStorage->removeOneBySourceUriPathAndHost($sourceUriPath, $host);
                    $forcePersist = true;
                } elseif ($isSame === true) {
                    $this->outputRedirectLine('<comment>~~</comment>', $redirect);
                    unset($hosts[$key]);
                    $skipped = true;
                }
            }
            if ($skipped === true && $hosts === []) {
                continue;
            }
            if ($forcePersist) {
                $this->persistenceManager->persistAll();
            }
            try {
                $redirects = $this->redirectStorage->addRedirection($sourceUriPath, $targetUriPath, $statusCode, $hosts);
                foreach ($redirects as $redirect) {
                    $this->outputRedirectLine('<info>++</info>', $redirect);
                }
                $this->persistenceManager->persistAll();
            } catch (Exception $exception) {
                \TYPO3\Flow\var_dump($exception);
                $this->outputLine('<error>!!</error> [%d] %s', [$statusCode, $sourceUriPath]);
            }
            $counter++;
            if ($counter % 50 === 0) {
                $this->persistenceManager->persistAll();
                $this->persistenceManager->clearState();
            }
        }
        $this->outputLine();
        $this->outputLegend();
    }

    /**
     * @param RedirectInterface $redirect
     * @param string $sourceUriPath
     * @param string $targetUriPath
     * @param string $host
     * @param integer $statusCode
     * @return boolean
     */
    protected function isSame($sourceUriPath, $targetUriPath, $host, $statusCode, RedirectInterface $redirect = null)
    {
        if ($redirect === null) {
            return false;
        }
        return $redirect->getSourceUriPath() === $sourceUriPath && $redirect->getTargetUriPath() === $targetUriPath && $redirect->getHost() === $host && $redirect->getStatusCode() === (integer)$statusCode;
    }

    /**
     * @return void
     */
    protected function outputWarningForLeagueCsvPackage()
    {
        $this->outputLine();
        $this->outputLine('<info>Import/Export</info> features require the package <b>league/csv</b>');
        $this->outputLine();
        $this->outputLine('Open your shell and launch:');
        $this->outputLine('# <comment>composer require league/csv</comment>');
        $this->outputLine();
        $this->sendAndExit(1);
    }

    /**
     * Removes a redirection
     *
     * This command deletes a redirection from the RedirectRepository
     *
     * @param string $source The source URI path of the redirection to remove, as given by redirect:list
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeCommand($source, $host = null)
    {
        $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($source, $host);
        if ($redirect === null) {
            $this->outputLine('There is no redirection with the source URI path "%s"', [$source]);
            $this->quit(1);
        }
        $this->redirectStorage->removeOneBySourceUriPathAndHost($source, $host);
        $this->outputLine('Removed redirection with the source URI path "%s"', [$source]);
    }

    /**
     * Removes all redirections
     *
     * This command deletes all redirections from the RedirectRepository
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeAllCommand($host = null)
    {
        $this->redirectStorage->removeAll($host);
        if ($host === null) {
            $this->outputLine('Removed all redirections with no host attached');
        } else {
            $this->outputLine('Removed all redirections for host "%s"', [$host]);
        }
    }

    /**
     * Adds a redirection
     *
     * This command adds a new redirection to the RedirectRepository using the RedirectionService
     *
     * @param string $source The relative URI path that should trigger the redirect
     * @param string $target The relative URI path that should be redirected to
     * @param integer $statusCode The status code of the redirect header
     * @param string $host Host or host pattern to match the redirect
     * @param boolean $force Replace existing redirection (based on the source URI)
     * @return void
     */
    public function addCommand($source, $target, $statusCode, $host = null, $force = false)
    {
        $this->outputLine();
        $this->outputLine('<b>Create a redirection ...</b>');
        $this->outputLine();
        $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($source, $host);
        $isSame = $this->isSame($source, $target, $host, $statusCode, $redirect);
        if ($redirect !== null && $isSame === false && $force === false) {
            $this->outputLine('A redirection with the same source URI exist, see bellow:');
            $this->outputLine();
            $this->outputRedirectLine('<error>!!</error>', $redirect);
            $this->outputLine();
            $this->outputLine('Use --force to replace it');
            $this->outputLine();
            $this->sendAndExit(1);
        } elseif ($redirect !== null && $isSame === false && $force === true) {
            $this->redirectStorage->removeOneBySourceUriPathAndHost($source, $host);
            $this->outputRedirectLine('<info>--</info>', $redirect);
            $this->persistenceManager->persistAll();
        } elseif ($redirect !== null && $isSame === true) {
            $this->outputRedirectLine('<comment>~~</comment>', $redirect);
            $this->outputLine();
            $this->outputLegend();
            $this->sendAndExit();
        }
        $redirects = $this->redirectStorage->addRedirection($source, $target, $statusCode, [$host]);
        $redirect = reset($redirects);
        $this->outputRedirectLine('<info>++</info>', $redirect);
        $this->outputLine();
        $this->outputLegend();
    }

    /**
     * @param string $prefix
     * @param RedirectInterface $redirect
     */
    protected function outputRedirectLine($prefix, RedirectInterface $redirect)
    {
        $this->outputLine('   %s %s <info>=></info> %s <comment>(%d)</comment> - %s', [
            $prefix,
            $redirect->getSourceUriPath(),
            $redirect->getTargetUriPath(),
            $redirect->getStatusCode(),
            $redirect->getHost() ?: 'no host'
        ]);
    }

    /**
     * @return void
     */
    protected function outputLegend()
    {
        $this->outputLine('<b>Legend</b>');
        $this->outputLine();
        $this->outputLine('   <info>++</info> Redirection created');
        $this->outputLine('   <info>--</info> Redirection removed');
        $this->outputLine('   <comment>~~</comment> Redirection do not need update');
        $this->outputLine('   <error>!!</error> Error');
        $this->outputLine();
    }
}
