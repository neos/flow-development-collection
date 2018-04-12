<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

/**
 * The base class for code migrations.
 */
abstract class AbstractMigration
{
    /**
     * @var Manager
     */
    protected $migrationsManager;

    /**
     * @var string
     */
    protected $sourcePackageKey;

    /**
     * @var array
     */
    protected $targetPackageData;

    /**
     * @var array
     */
    protected $operations = array('searchAndReplace' => array(), 'searchAndReplaceRegex' => array(), 'moveFile' => array(), 'deleteFile' => array());

    /**
     * @var array
     */
    protected $notes = array();

    /**
     * @var array
     */
    protected $warnings = array();

    /**
     * @param Manager $manager
     * @param string $packageKey
     */
    public function __construct(Manager $manager, $packageKey)
    {
        $this->migrationsManager = $manager;
        $this->sourcePackageKey = $packageKey;
    }

    /**
     * Resets internal state and sets the target package data.
     *
     * @param array $targetPackageData
     * @return void
     */
    public function prepare(array $targetPackageData)
    {
        $this->operations = array('searchAndReplace' => array(), 'searchAndReplaceRegex' => array(), 'moveFile' => array(), 'deleteFile' => array());
        $this->targetPackageData = $targetPackageData;
    }

    /**
     * Returns the package key this migration comes from.
     *
     * @return string
     */
    public function getSourcePackageKey()
    {
        return $this->sourcePackageKey;
    }

    /**
     * Returns the identifier of this migration, e.g. 'Neos.Flow-20120126163610'.
     *
     * @return string
     */
    abstract public function getIdentifier();

    /**
     * Returns the version of this migration, e.g. '20120126163610'.
     *
     * @return string
     */
    public function getVersionNumber()
    {
        return substr(strrchr(get_class($this), 'Version'), 7);
    }

    /**
     * Returns the first line of the migration class doc comment
     *
     * @return string
     */
    public function getDescription()
    {
        $reflectionClass = new \ReflectionClass($this);
        $lines = explode(chr(10), $reflectionClass->getDocComment());
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === '/**' || $line === '*' || $line === '*/' || strpos($line, '* @') !== false) {
                continue;
            }
            return preg_replace('/\s*\\/?[\\\\*]*\s?(.*)$/', '$1', $line);
        }
    }

    /**
     * Anything that needs to be done in the migration when migrating
     * into the "up" direction needs to go into this method.
     *
     * It will be called by the Manager upon migration.
     *
     * @return void
     * @api
     */
    abstract public function up();

    /**
     * @return void
     */
    public function execute()
    {
        $this->applySearchAndReplaceOperations();
        $this->applyFileOperations();
    }

    /**
     * This can be used to show a note to the developer.
     *
     * If changes cannot be automated or something needs to be
     * adjusted  manually for other reasons, leave a note for the
     * developer. The notes will be shown together after migrations
     * have been run.
     *
     * @param string $note
     * @return void
     * @see showWarning
     * @api
     */
    protected function showNote($note)
    {
        $this->notes[md5($note)] = $note;
    }

    /**
     * @return boolean
     */
    public function hasNotes()
    {
        return $this->notes !== array();
    }

    /**
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * This can be used to show a warning to the developer.
     *
     * Similar to showNote, but the output is given a stronger
     * emphasis. The warnings will be shown together after migrations
     * have been run.
     *
     * @param string $warning
     * @return void
     * @see showNote
     * @api
     */
    protected function showWarning($warning)
    {
        $this->warnings[md5($warning)] = $warning;
    }

    /**
     * @return boolean
     */
    public function hasWarnings()
    {
        return $this->warnings !== array();
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Does a simple search and replace on all (textual) files. The filter array can be
     * used to give file extensions to limit the operation to.
     *
     * @param string $search
     * @param string $replacement
     * @param array|string $filter either an array with file extensions to consider or the full path to a single file to process
     * @return void
     * @api
     */
    protected function searchAndReplace($search, $replacement, $filter = array('php', 'yaml', 'html'))
    {
        $this->operations['searchAndReplace'][] = array($search, $replacement, $filter, false);
    }

    /**
     * Does a regex search and replace on all (textual) files. The filter array can be
     * used to give file extensions to limit the operation to.
     *
     * The patterns are used as is, no quoting is done. A closure can be given for
     * the $replacement variable. It should return a string and is given an
     * array of matches as parameter.
     *
     * @param string $search
     * @param string|\Closure $replacement
     * @param array|string $filter either an array with file extensions to consider or the full path to a single file to process
     * @return void
     * @api
     */
    protected function searchAndReplaceRegex($search, $replacement, $filter = array('php', 'yaml', 'html'))
    {
        $this->operations['searchAndReplace'][] = array($search, $replacement, $filter, true);
    }

    /**
     * Rename a class from $oldName to $newName.
     *
     * This expects fully qualified class names, so proper refactoring
     * can be done.
     *
     * @param string $oldName
     * @param string $newName
     * @return void
     * @throws \LogicException
     */
    protected function renameClass($oldName, $newName)
    {
        throw new \LogicException('renameClass is not yet implemented, sorry!', 1335525001);
    }

    /**
     * Rename a class method.
     *
     * This expects a fully qualified class name, so proper refactoring
     * can be done.
     *
     * @param string $className the class that contains the method to be renamed
     * @param string $oldMethodName the method to be renamed
     * @param string $newMethodName the new method name
     * @param boolean $withInheritance if true, also rename method on child classes
     * @return void
     * @throws \LogicException
     */
    protected function renameMethod($className, $oldMethodName, $newMethodName, $withInheritance = true)
    {
        throw new \LogicException('renameMethod is not yet implemented, sorry!', 1479293733);
    }

    /**
     * Move a file (or directory) from $oldPath to $newPath.
     *
     * If $oldPath ends with a * everything starting with $oldPath
     * will be moved into $newPath (which then is created as a directory,
     * if it does not yet exist).
     *
     * @param string $oldPath
     * @param string $newPath
     * @return void
     */
    protected function moveFile($oldPath, $newPath)
    {
        $this->operations['moveFile'][] = array($oldPath, $newPath);
    }

    /**
     * Delete a file.
     *
     * @param string $pathAndFileName
     * @return void
     */
    protected function deleteFile($pathAndFileName)
    {
        $this->operations['deleteFile'][] = array($pathAndFileName);
    }

    /**
     * Apply the given processor to the raw results of loading the given configuration
     * type for the package from YAML. If multiple files exist (context configuration)
     * all are processed independently.
     *
     * @param string $configurationType One of ConfigurationManager::CONFIGURATION_TYPE_*
     * @param \Closure $processor
     * @param boolean $saveResult
     * @return void
     */
    protected function processConfiguration($configurationType, \Closure $processor, $saveResult = false)
    {
        if (is_dir($this->targetPackageData['path'] . '/Configuration') === false) {
            return;
        }

        $yamlPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'] . '/Configuration', 'yaml', true);
        $configurationPathsAndFilenames = array_filter($yamlPathsAndFilenames,
            function ($pathAndFileName) use ($configurationType) {
                if (strpos(basename($pathAndFileName, '.yaml'), $configurationType) === 0) {
                    return true;
                } else {
                    return false;
                }
            }
        );

        $yamlSource = new YamlSource();
        foreach ($configurationPathsAndFilenames as $pathAndFilename) {
            $originalConfiguration = $configuration = $yamlSource->load(substr($pathAndFilename, 0, -5));
            $processor($configuration);
            if ($saveResult === true && $configuration !== $originalConfiguration) {
                $yamlSource->save(substr($pathAndFilename, 0, -5), $configuration);
            }
        }
    }

    /**
     * Move a settings path from "source" to "destination"; best to be used when package names change.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     */
    protected function moveSettingsPaths($sourcePath, $destinationPath)
    {
        $this->processConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (array &$configuration) use ($sourcePath, $destinationPath) {
                $sourceConfigurationValue = Arrays::getValueByPath($configuration, $sourcePath);
                $destinationConfigurationValue = Arrays::getValueByPath($configuration, $destinationPath);

                if ($sourceConfigurationValue !== null) {
                    // source exists, so we need to move source to destination.

                    if ($destinationConfigurationValue !== null) {
                        // target exists as well; we need to MERGE source and target.
                        $destinationConfigurationValue = Arrays::arrayMergeRecursiveOverrule($sourceConfigurationValue, $destinationConfigurationValue);
                    } else {
                        // target does NOT exist; we directly set target = source
                        $destinationConfigurationValue = $sourceConfigurationValue;
                    }

                    // set the config on the new path
                    $configuration = Arrays::setValueByPath($configuration, $destinationPath, $destinationConfigurationValue);

                    // Unset the old configuration
                    $configuration = Arrays::unsetValueByPath($configuration, $sourcePath);

                    // remove empty keys before our removed key (if it exists)
                    $sourcePathExploded = explode('.', $sourcePath);
                    for ($length = count($sourcePathExploded) - 1; $length > 0; $length--) {
                        $temporaryPath = array_slice($sourcePathExploded, 0, $length);
                        $valueAtPath = Arrays::getValueByPath($configuration, $temporaryPath);
                        if (empty($valueAtPath)) {
                            $configuration = Arrays::unsetValueByPath($configuration, $temporaryPath);
                        } else {
                            break;
                        }

                    }
                }
            },
            true
        );
    }

    /**
     * Applies all registered searchAndReplace operations.
     *
     * @return void
     */
    protected function applySearchAndReplaceOperations()
    {
        foreach (Files::getRecursiveDirectoryGenerator($this->targetPackageData['path'], null, true) as $pathAndFilename) {
            $pathInfo = pathinfo($pathAndFilename);
            if (!isset($pathInfo['filename'])) {
                continue;
            }
            if (strpos($pathAndFilename, 'Migrations/Code') !== false) {
                continue;
            }

            foreach ($this->operations['searchAndReplace'] as $operation) {
                list($search, $replacement, $filter, $regularExpression) = $operation;
                if (is_array($filter)) {
                    if ($filter !== array() && (!isset($pathInfo['extension']) || !in_array($pathInfo['extension'], $filter, true))) {
                        continue;
                    }
                } elseif (substr($pathAndFilename, -strlen($filter)) !== $filter) {
                    continue;
                }
                Tools::searchAndReplace($search, $replacement, $pathAndFilename, $regularExpression);
            }
        }
    }

    /**
     * Applies all registered moveFile operations.
     *
     * @return void
     */
    protected function applyFileOperations()
    {
        foreach ($this->operations['moveFile'] as $operation) {
            $oldPath = Files::concatenatePaths(array($this->targetPackageData['path'] . '/' . $operation[0]));
            $newPath = Files::concatenatePaths(array($this->targetPackageData['path'] . '/' . $operation[1]));

            if (substr($oldPath, -1) === '*') {
                $oldPath = substr($oldPath, 0, -1);
                if (!file_exists($oldPath)) {
                    continue;
                }
                if (!file_exists($newPath)) {
                    Files::createDirectoryRecursively($newPath);
                }
                if (!is_dir($newPath)) {
                    continue;
                }
                foreach (Files::getRecursiveDirectoryGenerator($this->targetPackageData['path'], null, true) as $pathAndFilename) {
                    if (substr_compare($pathAndFilename, $oldPath, 0, strlen($oldPath)) === 0) {
                        $relativePathAndFilename = substr($pathAndFilename, strlen($oldPath));
                        if (!is_dir(dirname(Files::concatenatePaths(array($newPath, $relativePathAndFilename))))) {
                            Files::createDirectoryRecursively(dirname(Files::concatenatePaths(array($newPath, $relativePathAndFilename))));
                        }
                        Git::move($pathAndFilename, Files::concatenatePaths(array($newPath, $relativePathAndFilename)));
                    }
                }
            } else {
                $oldPath = Files::concatenatePaths(array($this->targetPackageData['path'] . '/' . $operation[0]));
                $newPath = Files::concatenatePaths(array($this->targetPackageData['path'] . '/' . $operation[1]));
                Git::move($oldPath, $newPath);
            }
        }

        foreach ($this->operations['deleteFile'] as $operation) {
            $filename = Files::concatenatePaths(array($this->targetPackageData['path'] . '/' . $operation[0]));
            if (file_exists($filename)) {
                Git::remove($filename);
            }
        }
    }
}
