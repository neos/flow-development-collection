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
use Neos\RedirectHandler\Redirect;
use Neos\RedirectHandler\RedirectInterface;
use Neos\RedirectHandler\Storage\RedirectStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * Command controller for tasks related to redirects
 *
 * @Flow\Scope("singleton")
 */
class RedirectCommandController extends CommandController
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
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $logger;

    /**
     * @param string $host Filter redirects by the given hostname
     * @param string $match Match source or target URI by a regular expression
     * @return void
     */
    public function listCommand($host = null, $match = null)
    {
        $outputByHost = function ($host = null) use ($match) {
            $redirects = $this->redirectStorage->getAll($host);
            $this->outputLine();
            if ($host !== null) {
                $this->outputLine('<info>==</info> <b>Redirect for %s</b>', [$host]);
            } else {
                $this->outputLine('<info>==</info> <b>Redirects valid for all hosts</b>', [$host]);
            }
            if ($match !== null) {
                $this->outputLine('   <info>++</info> <b>Only showing redirects where source or target URI matches <u>%s</u></b>', [$match]);
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
            $hosts = array_merge([null], $this->redirectStorage->getDistinctHosts());
            array_map($outputByHost, $hosts);
        }
        $this->outputLine();
    }

    /**
     * Save all redirectection to a CSV file
     *
     * @param string $filename CSV file path, if null use standard output
     * @param string $host Optional full qualified host name
     * @return void
     */
    public function exportCommand($filename = null, $host = null)
    {
        if (!class_exists(Writer::class)) {
            $this->outputWarningForLeagueCsvPackage();
        }
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        if ($host !== null) {
            $redirects = $this->redirectStorage->getAll($host);
        } else {
            $redirects = new \AppendIterator();
            $redirects->append($this->redirectStorage->getAll(null));
            foreach ($this->redirectStorage->getDistinctHosts() as $host) {
                $redirects->append($this->redirectStorage->getAll($host));
            }
        }
        /** @var $redirect RedirectInterface */
        foreach ($redirects as $redirect) {
            $writer->insertOne([
                $redirect->getSourceUriPath(),
                $redirect->getTargetUriPath(),
                $redirect->getStatusCode(),
                $redirect->getHost()
            ]);
        }
        if ($filename === null) {
            $writer->output();
        } else {
            file_put_contents($filename, (string)$writer);
        }
    }

    /**
     * Import redirects from a CSV file
     *
     * @param string $filename CSV file path
     * @return void
     */
    public function importCommand($filename)
    {
        $hasErrors = false;
        $this->outputLine();
        if (!class_exists(Reader::class)) {
            $this->outputWarningForLeagueCsvPackage();
        }
        if (!is_readable($filename)) {
            $this->outputLine('<error>Sorry, but the file "%s" is not readable or does not exist...</error>', [$filename]);
            $this->outputLine();
            $this->sendAndExit(1);
        }
        $this->outputLine('<b>Import redirects from "%s"</b>', [$filename]);
        $this->outputLine();
        $reader = Reader::createFromPath($filename);
        $counter = 0;
        foreach ($reader as $index => $row) {
            $skipped = false;
            list($sourceUriPath, $targetUriPath, $statusCode, $hosts) = $row;
            $hosts = Arrays::trimExplode('|', $hosts);
            if ($hosts === []) {
                $hosts = [null];
            }
            $forcePersist = false;
            foreach ($hosts as $key => $host) {
                $host = trim($host);
                $host = $host === '' ? null : $host;
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
                $redirects = $this->redirectStorage->addRedirect($sourceUriPath, $targetUriPath, $statusCode, $hosts);
                /** @var Redirect $redirect */
                foreach ($redirects as $redirect) {
                    $this->outputRedirectLine('<info>++</info>', $redirect);
                    $messageArguments = [$redirect->getSourceUriPath(), $redirect->getTargetUriPath(), $redirect->getStatusCode(), $redirect->getHost() ?: 'all host'];
                    $this->logger->log(vsprintf('Redirect import success, sourceUriPath=%s, targetUriPath=%s, statusCode=%d, hosts=%s', $messageArguments), LOG_ERR);
                }
                $this->persistenceManager->persistAll();
            } catch (Exception $exception) {
                $messageArguments = [$sourceUriPath, $targetUriPath, $statusCode, $hosts ? json_encode($hosts) : 'all host'];
                $this->outputLine('   <error>!!</error> %s => %s <comment>(%d)</comment> - %s', $messageArguments);
                $this->outputLine('      Message: %s', [$exception->getMessage()]);
                $this->logger->log(vsprintf('Redirect import error, sourceUriPath=%s, targetUriPath=%s, statusCode=%d, hosts=%s', $messageArguments), LOG_ERR);
                $this->logger->logException($exception);
                $hasErrors = true;
            }
            $counter++;
            if ($counter % 50 === 0) {
                $this->persistenceManager->persistAll();
                $this->persistenceManager->clearState();
            }
        }
        $this->outputLine();
        if ($hasErrors === true) {
            $this->outputLine('   <error>!!</error> some errors appeared during import, please check the log or the CLI output.');
        }
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
     * Removes a redirect
     *
     * This command deletes a redirect from the RedirectRepository
     *
     * @param string $source The source URI path of the redirect to remove, as given by redirect:list
     * @param string $host Full qualified host name
     * @return void
     */
    public function removeCommand($source, $host = null)
    {
        $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($source, $host);
        if ($redirect === null) {
            $this->outputLine('There is no redirect with the source URI path "%s", maybe you forgot the --host argument ?', [$source]);
            $this->quit(1);
        }
        $this->redirectStorage->removeOneBySourceUriPathAndHost($source, $host);
        $this->outputLine('Removed redirect with the source URI path "%s"', [$source]);
    }

    /**
     * Removes all redirects
     *
     * This command deletes all redirects from the RedirectRepository
     *
     * @return void
     */
    public function removeAllCommand()
    {
        $this->redirectStorage->removeAll();
        $this->outputLine('Removed all redirects');
    }

    /**
     * Removes all redirects by host
     *
     * This command deletes all redirects from the RedirectRepository by host value.
     * If ``all`` is entered the redirects valid for all hosts are deleted.
     *
     * @param string $host Fully qualified host name or `all` to delete redirects valid for all hosts
     * @return void
     */
    public function removeByHostCommand($host)
    {
        if ($host === 'all') {
            $this->redirectStorage->removeByHost(null);
            $this->outputLine('Removed redirects matching all hosts');
        } else {
            $this->redirectStorage->removeByHost($host);
            $this->outputLine('Removed all redirects for host "%s"', [$host]);
        }
    }

    /**
     * Adds a redirect
     *
     * This command adds a new redirect to the RedirectRepository using the RedirectService
     *
     * @param string $source The relative URI path that should trigger the redirect
     * @param string $target The relative URI path that should be redirected to
     * @param integer $statusCode The status code of the redirect header
     * @param string $host Full qualified host name
     * @param boolean $force Replace existing redirect (based on the source URI)
     * @return void
     */
    public function addCommand($source, $target, $statusCode, $host = null, $force = false)
    {
        $this->outputLine();
        $this->outputLine('<b>Create a redirect ...</b>');
        $this->outputLine();
        $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($source, $host, false);
        $isSame = $this->isSame($source, $target, $host, $statusCode, $redirect);
        if ($redirect !== null && $isSame === false && $force === false) {
            $this->outputLine('A redirect with the same source URI exist, see below:');
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
        $redirects = $this->redirectStorage->addRedirect($source, $target, $statusCode, [$host]);
        $redirect = reset($redirects);
        $this->outputRedirectLine('<info>++</info>', $redirect);
        $this->outputLine();
        $this->outputLegend();
    }

    /**
     * @param string $prefix
     * @param RedirectInterface $redirect
     * @return void
     */
    protected function outputRedirectLine($prefix, RedirectInterface $redirect)
    {
        $this->outputLine('   %s %s <info>=></info> %s <comment>(%d)</comment> - %s', [
            $prefix,
            $redirect->getSourceUriPath(),
            $redirect->getTargetUriPath(),
            $redirect->getStatusCode(),
            $redirect->getHost() ?: 'all host'
        ]);
    }

    /**
     * @return void
     */
    protected function outputLegend()
    {
        $this->outputLine('<b>Legend</b>');
        $this->outputLine();
        $this->outputLine('   <info>++</info> Redirect created');
        $this->outputLine('   <info>--</info> Redirect removed');
        $this->outputLine('   <comment>~~</comment> Redirect not modified');
        $this->outputLine('   <error>!!</error> Error');
        $this->outputLine();
    }
}
