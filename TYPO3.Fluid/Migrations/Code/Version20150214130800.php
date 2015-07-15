<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Fluid".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PhpAnalyzer;
use TYPO3\Neos\ViewHelpers\Backend\JavascriptConfigurationViewHelper;
use TYPO3\Neos\ViewHelpers\Link\NodeViewHelper;

/**
 * Add "escapeOutput" property to existing ViewHelpers to ensure backwards-compatibility
 *
 * Note: If an affected ViewHelper does not create HTML output, you should remove this property (or set it TRUE) in order to ensure sanitization of the output
 */
class Version20150214130800 extends AbstractMigration {

	/**
	 * @return void
	 */
	public function up() {
		$affectedViewHelperClassNames = array();
		$allPathsAndFilenames = Files::readDirectoryRecursively($this->targetPackageData['path'], '.php', TRUE);
		foreach ($allPathsAndFilenames as $pathAndFilename) {
			if (substr($pathAndFilename, -14) !== 'ViewHelper.php') {
				continue;
			}
			$fileContents = file_get_contents($pathAndFilename);
			$className = (new PhpAnalyzer($fileContents))->extractFullyQualifiedClassName();
			if ($className === NULL) {
				$this->showWarning(sprintf('could not extract class name from file "%s"', $pathAndFilename));
				continue;
			}
			/** @noinspection PhpIncludeInspection */
			require_once $pathAndFilename;
			if (!class_exists($className)) {
				$this->showWarning(sprintf('could not load class "%s" extracted from file "%s"', $className, $pathAndFilename));
				continue;
			}
			$instance = new $className();

			$escapeOutput = ObjectAccess::getProperty($instance, 'escapeOutput', TRUE);
			if ($escapeOutput !== NULL) {
				continue;
			}
			$affectedViewHelperClassNames[] = $className;
			$this->searchAndReplaceRegex('/\R\s*class[^\{]+\R?\{(\s*)(?=.*?\})/s', '$0' . "\n\t" . '/**' . "\n\t" . ' * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.' . "\n\t" . ' * @see AbstractViewHelper::isOutputEscapingEnabled()' . "\n\t" . ' * @var boolean' . "\n\t" . ' */' . "\n\t" . 'protected $escapeOutput = FALSE;$1', $pathAndFilename);
		}

		if ($affectedViewHelperClassNames !== array()) {
			$this->showWarning('Added "escapeOutput" property to following ViewHelpers:' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', $affectedViewHelperClassNames) . PHP_EOL . PHP_EOL . 'If an affected ViewHelper does not render HTML output, you should set this property TRUE in order to ensure sanitization of the output!');
		}

		$this->addWarningsForAffectedViewHelpers($this->targetPackageData['path']);
	}

	/**
	 * Add a warning for each HTML file that uses one of the f:uri.* or the f:format.json ViewHelpers
	 *
	 * @param string $packagePath
	 * @return void
	 */
	protected function addWarningsForAffectedViewHelpers($packagePath) {
		$foundAffectedViewHelpers = array();
		$allPathsAndFilenames = Files::readDirectoryRecursively($packagePath, NULL, TRUE);
		foreach ($allPathsAndFilenames as $pathAndFilename) {
			$pathInfo = pathinfo($pathAndFilename);
			if (!isset($pathInfo['filename']) || $pathInfo['extension'] !== 'html') {
				continue;
			}
			$fileContents = file_get_contents($pathAndFilename);
			preg_match_all('/f\:(uri\.[\w]+|format\.json)/', $fileContents, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$viewHelperName = $match[1];
				if (!isset($foundAffectedViewHelpers[$viewHelperName])) {
					$foundAffectedViewHelpers[$viewHelperName] = array();
				}
				$truncatedPathAndFilename = substr($pathAndFilename, strlen($packagePath) + 1);
				if (!in_array($truncatedPathAndFilename, $foundAffectedViewHelpers[$viewHelperName])) {
					$foundAffectedViewHelpers[$viewHelperName][] = $truncatedPathAndFilename;
				}
			}
		}
		foreach ($foundAffectedViewHelpers as $viewHelperName => $filePathsAndNames) {
			$this->showWarning(sprintf('The behavior of the "%s" ViewHelper has been changed to produce escaped output.' . chr(10)
				. 'This package makes use of this ViewHelper in the following files:' . chr(10) . '- %s' . chr(10)
				. 'See upgrading instructions for further details.' . chr(10),
				$viewHelperName, implode(chr(10) . '- ', $filePathsAndNames)));
		}
	}

}
