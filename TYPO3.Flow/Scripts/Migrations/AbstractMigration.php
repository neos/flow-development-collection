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

use TYPO3\Flow\Core\Migrations\Tools;
use TYPO3\Flow\Utility\Files;

/**
 * The base class for code migrations.
 */
abstract class AbstractMigration {

	/**
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
	 * @param \TYPO3\Flow\Core\Migrations\Manager $manager
	 * @param string $packageKey
	 */
	public function __construct(\TYPO3\Flow\Core\Migrations\Manager $manager, $packageKey) {
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
	 * Returns the identifier of this migration, e.g. 'TYPO3.Flow-201201261636'.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->sourcePackageKey . '-' . substr(get_class($this), -12);
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
	 * Will show all notes and warnings accumulated.
	 *
	 * @return void
	 */
	public function outputNotesAndWarnings() {
		foreach (array('notes', 'warnings') as $type) {
			if ($this->$type === array()) {
				continue;
			}

			$this->outputLine();
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			$this->outputLine('   ' . ucfirst($type));
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			foreach ($this->$type as $note) {
				$this->outputLine('  * ' . $this->wrapAndPrefix($note));
			}
			$this->outputLine('  ' . str_repeat('-', self::MAXIMUM_LINE_LENGTH));
		}
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
		$this->notes[sha1($note)] = $note;
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
		$this->warnings[sha1($warning)] = $warning;
	}

	/**
	 * Does a simple search and replace on all (textual) files. The filter array can be
	 * used to give file extensions to limit the operation to.
	 *
	 * @param string $search
	 * @param string $replacement
	 * @param array $filter
	 * @return void
	 * @api
	 */
	protected function searchAndReplace($search, $replacement, array $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplace'][] = array($search, $replacement, $filter);
	}

	/**
	 * Does a regex search and replace on all (textual) files. The filter array can be
	 * used to give file extensions to limit the operation to.
	 *
	 * The patterns are used as is, no quoting is done.
	 *
	 * @param string $search
	 * @param string $replacement
	 * @param array $filter
	 * @return void
	 * @api
	 */
	protected function searchAndReplaceRegex($search, $replacement, array $filter = array('php', 'yaml', 'html')) {
		$this->operations['searchAndReplaceRegex'][] = array($search, $replacement, $filter);
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
	 * @return void
	 */
	protected function processConfiguration($configurationType, \Closure $processor, $saveResult = FALSE) {
		$yamlPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'] . '/Configuration', 'yaml', TRUE);
		$expectedConfigurationFileName = $configurationType . '.yaml';
		$configurationPathsAndFilenames = array_filter($yamlPathsAndFilenames,
			function ($pathAndFileName) use ($expectedConfigurationFileName) {
				if (basename($pathAndFileName) === $expectedConfigurationFileName) {
					return TRUE;
				} else {
					return FALSE;
				}
			}
		);

		$yamlSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		foreach ($configurationPathsAndFilenames as $pathAndFilename) {
			$configuration = $yamlSource->load(substr($pathAndFilename, 0, -5));
			$processor($configuration);
			if ($saveResult === TRUE) {
				$yamlSource->save(substr($pathAndFilename, 0, -5), $configuration);
			}
		}
	}

	/**
	 * Applies all registered searchAndReplace and searchAndReplaceRegex operations.
	 *
	 * @return void
	 */
	protected function applySearchAndReplaceOperations() {
		$allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], NULL, TRUE);
		foreach ($this->operations['searchAndReplace'] as $operation) {
			foreach ($allPathsAndFilenames as $pathAndFilename) {
				$pathInfo = pathinfo($pathAndFilename);
				if (!isset($pathInfo['filename'])) continue;
				if (strpos($pathAndFilename, 'Migrations/Code') !== FALSE) continue;

				if ($operation[2] !== array()) {
					if (!isset($pathInfo['extension']) || !in_array($pathInfo['extension'], $operation[2], TRUE)) {
						continue;
					}
				}
				Tools::searchAndReplace($operation[0], $operation[1], $pathAndFilename);
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
				if (!file_exists($newPath)) {
					Files::createDirectoryRecursively($newPath);
				}
				if (!is_dir($newPath) || !file_exists($oldPath)) {
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

?>