#!/usr/bin/env php
<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require (__DIR__ . '/../Classes/Utility/Files.php');
require (__DIR__ . '/../Classes/Exception.php');
require (__DIR__ . '/../Classes/Utility/Exception.php');

define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

if (FLOW3_SAPITYPE !== 'CLI') exit ('This script can only be executed from the command line.');

define('FLOW3_PATH_ROOT', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../../../') . '/'))));

if (!isset($argv[1]) || ($argv[1] !== '--migrate' && $argv[1] !== '--dryrun') || $argv[1] === '--help') {
	echo "
FLOW3 1.0 annotation migration script.

This script scans PHP files of all installed packages for annotations
and replaces them with an updated version.

MAKE SURE TO BACKUP YOUR CODE BEFORE RUNNING THIS SCRIPT!

Call this script with the --dryrun to see what would be changed.
Call this script with the --migrate to actually do the changes.

The parameter --packages-path allows you to overwrite the default
path " . FLOW3_PATH_ROOT . "Packages/. It has to be the second parameter:
  migrate-annotations.php --dryrun --packages-path /usr/local/

";

	exit(1);
}

if (isset($argv[2]) && strpos($argv[2], '--packages-path') === 0 && isset($argv[3]) ){
    define('FLOW3_PATH_PACKAGES', $argv[3]);
} else {
    define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');
}

$ormAnnotations = array('MappedSuperclass', 'InheritanceType', 'DiscriminatorColumn', 'DiscriminatorMap', 'Id', 'GeneratedValue', 'Version', 'JoinColumns', 'JoinColumn', 'Column', 'OneToOne', 'OneToMany', 'ManyToOne', 'ManyToMany', 'ElementCollection', 'JoinTable', 'Table', 'UniqueConstraint', 'Index', 'SequenceGenerator', 'ChangeTrackingPolicy', 'OrderBy', 'NamedQueries', 'NamedQuery', 'HasLifecycleCallbacks', 'PrePersist', 'PostPersist', 'PreUpdate', 'PostUpdate', 'PreRemove', 'PostRemove', 'PostLoad');

$allFlow3Annotations = array('AfterReturning', 'AfterThrowing', 'After', 'Around', 'Aspect', 'Autowiring', 'Before', 'Entity', 'FlushesCaches', 'Identity', 'IgnoreValidation', 'Inject', 'Internal', 'Lazy', 'Pointcut', 'Proxy', 'Scope', 'Session', 'Signal', 'Transient', 'ValueObject');
$singleArgumentAnnotations = array('AfterReturning', 'AfterThrowing', 'After', 'Around', 'Autowiring', 'Before', 'IgnoreValidation', 'Pointcut', 'Proxy', 'Scope', 'Session');

$pathsAndFilenames = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively(FLOW3_PATH_PACKAGES, '.php', TRUE);

foreach ($pathsAndFilenames as $pathAndFilename) {
	$pathInfo = pathinfo($pathAndFilename);

	if (!isset($pathInfo['filename'])) continue;
	if ($pathAndFilename === __FILE__) continue;
	if (strpos($pathAndFilename, 'Packages/Framework/') !== FALSE) continue;

	$file = file_get_contents($pathAndFilename);
	$fileBackup = $file;
	$file = preg_replace('/@(' . implode('|', $ormAnnotations) . ')/um', '@ORM\\\\$1', $file);

	foreach ($allFlow3Annotations as $annotation) {
		$file = str_replace('@' . strtolower($annotation), '@FLOW3\\' . $annotation, $file);
	}
	$file = str_replace('@skipCsrfProtection', '@FLOW3\SkipCsrfProtection', $file);
	$file = preg_replace('/\* @FLOW3\\\\(' . implode('|', $singleArgumentAnnotations) . ')\s+(.+)$/um', '* @FLOW3\\\\$1("$2")', $file);

		// convert some simple "booleans"
	$file = str_replace('@FLOW3\Autowiring("off")', '@FLOW3\Autowiring(false)', $file);
	$file = str_replace('@FLOW3\Proxy("disable")', '@FLOW3\Proxy(false)', $file);
	$file = str_replace('@FLOW3\Session("autoStart=true")', '@FLOW3\Session(autoStart=true)', $file);

		// validation annotations
	$file = preg_replace('/\* @validate\s+(.+)$/ume', 'convertValidateAnnotation(\'$1\')', $file);

		// convert "introduce" annotation options
	$file = preg_replace('/\* @introduce\s+([A-Za-z0-9\\\\]+)[\s,]+(.+)$/um', '* @FLOW3\\\\Introduce("$2", interfaceName="$1")', $file);
	$file = preg_replace('/\* @introduce\s+(.+)$/um', '* @FLOW3\\\\Introduce("$1")', $file);

	$shortPathAndFilename = substr($pathAndFilename, strlen(FLOW3_PATH_ROOT));
	if ($file !== $fileBackup) {
		if ($argv[1] === '--migrate') {
			if (strpos($file, 'use TYPO3\FLOW3\Annotations as FLOW3;') === FALSE) {
				$file = preg_replace('/(<\?php.*)(\/\*\*.+(?:class|interface).+)/Usm', '$1' . 'use Doctrine\ORM\Mapping as ORM;' . chr(10) . 'use TYPO3\FLOW3\Annotations as FLOW3;' . chr(10) . chr(10) . '$2', $file);
			}
			echo 'Updated           ' . $shortPathAndFilename . chr(10);
			file_put_contents($pathAndFilename, $file);
		} else {
			echo 'Would update      ' . $shortPathAndFilename . chr(10);
		}
	}
	unset($file);

}

echo "\nDone.\n";

function convertValidateAnnotation($value) {
	$result = array();
	$validatorConfiguration = parseValidatorAnnotation($value);
	foreach ($validatorConfiguration['validators'] as $validators) {
		if (isset($validatorConfiguration['argumentName'])) {
			$annotation = '	 * @FLOW3\\Validate("$' . $validatorConfiguration['argumentName'] . '", type="' . $validators['validatorName'] . '"';
		} else {
			$annotation = '	 * @FLOW3\\Validate(type="' . $validators['validatorName'] . '"';
		}
		if (isset($validators['validatorOptions']) && $validators['validatorOptions'] !== array()) {
			$options = array();
			foreach($validators['validatorOptions'] as $k => $v) {
				if (is_numeric($v)) {
					$options[] = '"' . $k . '"' . '=' . $v;
				} else {
					$options[] = '"' . $k . '"' . '="' . $v . '"';
				}
			}
			$annotation .= ', options={ ' . implode(', ', $options) . ' }';
		}
		$result[] = $annotation . ')';
	}
	return implode(chr(10), $result);
}
function parseValidatorAnnotation($validateValue) {
	$PATTERN_MATCH_VALIDATORS = '/
			(?:^|,\s*)
			(?P<validatorName>[a-z0-9\\\\]+)
			\s*
			(?:\(
				(?P<validatorOptions>(?:\s*[a-z0-9]+\s*=\s*(?:
					"(?:\\\\"|[^"])*"
					|\'(?:\\\\\'|[^\'])*\'
					|(?:\s|[^,"\']*)
				)(?:\s|,)*)*)
			\))?
		/ixS';
	$matches = array();
	if ($validateValue[0] === '$') {
		$parts = explode(' ', $validateValue, 2);
		$validatorConfiguration = array('argumentName' => ltrim($parts[0], '$'), 'validators' => array());
		preg_match_all($PATTERN_MATCH_VALIDATORS, $parts[1], $matches, PREG_SET_ORDER);
	} else {
		$validatorConfiguration = array('validators' => array());
		preg_match_all($PATTERN_MATCH_VALIDATORS, $validateValue, $matches, PREG_SET_ORDER);
	}

	foreach ($matches as $match) {
		$validatorOptions = array();
		if (isset($match['validatorOptions'])) {
			$validatorOptions = parseValidatorOptions($match['validatorOptions']);
		}
		$validatorConfiguration['validators'][] = array('validatorName' => $match['validatorName'], 'validatorOptions' => $validatorOptions);
	}

	return $validatorConfiguration;
}

function parseValidatorOptions($rawValidatorOptions) {
	$validatorOptions = array();
	$parsedValidatorOptions = array();
	preg_match_all('/
		\s*
		(?P<optionName>[a-z0-9]+)
		\s*=\s*
		(?P<optionValue>
			"(?:\\\\"|[^"])*"
			|\'(?:\\\\\'|[^\'])*\'
			|(?:\s|[^,"\']*)
		)
	/ixS', $rawValidatorOptions, $validatorOptions, PREG_SET_ORDER);
	foreach ($validatorOptions as $validatorOption) {
		$parsedValidatorOptions[trim($validatorOption['optionName'])] = trim($validatorOption['optionValue']);
	}
	array_walk($parsedValidatorOptions, 'unquoteString');
	return $parsedValidatorOptions;
}

function unquoteString(&$quotedValue) {
	switch ($quotedValue[0]) {
		case '"':
			$quotedValue = str_replace('\"', '"', trim($quotedValue, '"'));
		break;
		case '\'':
			$quotedValue = str_replace('\\\'', '\'', trim($quotedValue, '\''));
		break;
	}
	$quotedValue = str_replace('\\\\', '\\', $quotedValue);
}

?>
