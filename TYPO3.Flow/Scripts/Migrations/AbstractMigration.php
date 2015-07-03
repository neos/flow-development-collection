<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Configuration\Source\YamlSource;
use TYPO3\Flow\Utility\Files;

/**
 * The base class for code migrations.
 */
abstract class AbstractMigration {

	/**
	 * @deprecated since 3.0. Migrations must not have any direct output, use addNote() or addWarning() instead
	 * @var integer
	 */
	const MAXIMUM_LINE_LENGTH = 79;

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
	public function __construct(Manager $manager, $packageKey) {
		$this->migrationsManager = $manager;
		$this->sourcePackageKey = $packageKey;
	}

	/**
	 * Resets internal state and sets the target package data.
	 *
	 * @param array $targetPackageData
	 * @return void
	 */
	public function prepare(array $targetPackageData) {
		$this->operations = array('searchAndReplace' => array(), 'searchAndReplaceRegex' => array(), 'moveFile' => array(), 'deleteFile' => array());
		$this->targetPackageData = $targetPackageData;
	}

	/**
	 * Returns the package key this migration comes from.
	 *
	 * @return string
	 */
	public function getSourcePackageKey() {
		return $this->sourcePackageKey;
	}

	/**
	 * Returns the identifier of this migration, e.g. 'TYPO3.Flow-20120126163610'.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->sourcePackageKey . '-' . $this->getVersionNumber();
	}

	/**
	 * Returns the version of this migration, e.g. '20120126163610'.
	 *
	 * @return string
	 */
	public function getVersionNumber() {
		return substr(strrchr(get_class($this), 'Version'), 7);
	}

	/**
	 * Returns the first line of the migration class doc comment
	 *
	 * @return string
	 */
	public function getDescription() {
		$reflectionClass = new \ReflectionClass($this);
		$lines = explode(chr(10), $reflectionClass->getDocComment());
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || $line === '/**' || $line === '*' || $line === '*/' || strpos($line, '* @') !== FALSE) {
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
	public function execute() {
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
	protected function showNote($note) {
		$this->notes[md5($note)] = $note;
	}

	/**
	 * @return boolean
	 */
	public function hasNotes() {
		return $this->notes !== array();
	}

	/**
	 * @return array
	 */
	public function getNotes() {
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
	protected function showWarning($warning) {
		$this->warnings[md5($warning)] = $warning;
	}

	/**
	 * @return boolean
	 */
	public function hasWarnings() {
		return $this->warnings !== array();
	}

	/**
	 * @return array
	 */
	public function getWarnings() {
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
	protected function searchAndReplace($search, $replacement, $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplace'][] = array($search, $replacement, $filter, FALSE);
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
	protected function searchAndReplaceRegex($search, $replacement, $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplace'][] = array($search, $replacement, $filter, TRUE);
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
	protected function renameClass($oldName, $newName) {
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
	protected function renameMethod($className, $oldMethodName, $newMethodName, $withInheritance = TRUE) {
		throw new \LogicException('renameClass is not yet implemented, sorry!', 1335525001);
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
	protected function moveFile($oldPath, $newPath) {
		$this->operations['moveFile'][] = array($oldPath, $newPath);
	}

	/**
	 * Delete a file.
	 *
	 * @param string $pathAndFileName
	 * @return void
	 */
	protected function deleteFile($pathAndFileName) {
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
	protected function processConfiguration($configurationType, \Closure $processor, $saveResult = FALSE) {
		if (is_dir($this->targetPackageData['path'] . '/Configuration') === FALSE) {
			return;
		}

		$yamlPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'] . '/Configuration', 'yaml', TRUE);
		$configurationPathsAndFilenames = array_filter($yamlPathsAndFilenames,
			function ($pathAndFileName) use ($configurationType) {
				if (strpos(basename($pathAndFileName, '.yaml'), $configurationType) === 0) {
					return TRUE;
				} else {
					return FALSE;
				}
			}
		);

		$yamlSource = new YamlSource();
		foreach ($configurationPathsAndFilenames as $pathAndFilename) {
			$originalConfiguration = $configuration = $yamlSource->load(substr($pathAndFilename, 0, -5));
			$processor($configuration);
			if ($saveResult === TRUE && $configuration !== $originalConfiguration) {
				$yamlSource->save(substr($pathAndFilename, 0, -5), $configuration);
			}
		}
	}

	/**
	 * Applies all registered searchAndReplace operations.
	 *
	 * @return void
	 */
	protected function applySearchAndReplaceOperations() {
		$allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], NULL, TRUE);
		foreach ($allPathsAndFilenames as $pathAndFilename) {
			$pathInfo = pathinfo($pathAndFilename);
			if (!isset($pathInfo['filename'])) continue;
			if (strpos($pathAndFilename, 'Migrations/Code') !== FALSE) continue;

			foreach ($this->operations['searchAndReplace'] as $operation) {
				list($search, $replacement, $filter, $regularExpression) = $operation;
				if (is_array($filter)) {
					if ($filter !== array() && (!isset($pathInfo['extension']) || !in_array($pathInfo['extension'], $filter, TRUE))) {
						continue;
					}
				} elseif ($pathAndFilename !== $filter) {
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
	protected function applyFileOperations() {
		$allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], NULL, TRUE);
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
				foreach ($allPathsAndFilenames as $pathAndFilename) {
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

	/**
	 * The given text is word-wrapped and each line after the first one is
	 * prefixed with $prefix.
	 *
	 * @deprecated since 3.0. Migrations must not have any direct output, use addNote() or addWarning() instead
	 * @param string $text
	 * @param string $prefix
	 * @return string
	 */
	protected function wrapAndPrefix($text, $prefix = '    ') {
		$text = explode(chr(10), wordwrap($text, self::MAXIMUM_LINE_LENGTH, chr(10), TRUE));
		return implode(PHP_EOL . $prefix, $text);
	}

	/**
	 * Outputs specified text to the console window and appends a line break.
	 *
	 * You can specify arguments that will be passed to the text via sprintf
	 *
	 * @deprecated since 3.0. Migrations must not have any direct output, use addNote() or addWarning() instead
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		echo $text . PHP_EOL;
	}

}
