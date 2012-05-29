<?php
/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This script is used to update the available Internet Media Types in
 * Utility\MediaTypes from the mime.types file in the Apache HTTPD SVN
 * repository.
 *
 * It is a tool for use by the FLOW3 maintainers and can safely be ignored
 * by FLOW3 users.
 */

$mediaTypesClassPathAndFilename = __DIR__ . '/../Classes/Utility/MediaTypes.php';

$rawList = file_get_contents('http://svn.apache.org/viewvc/httpd/httpd/branches/2.4.x/docs/conf/mime.types?revision=HEAD&view=co');

$mediaTypesAndFileExtensions = array();

foreach(explode("\n", $rawList) as $line) {
	preg_match_all('/^([a-z][^\s]+)\s+(.+)$/', $line, $matches, PREG_SET_ORDER);
	if (count($matches) === 1) {
		$mediaTypesAndFileExtensions[$matches[0][1]] = preg_split('/\s+/', $matches[0][2]);
	}
}

ksort($mediaTypesAndFileExtensions);

$mediaTypesToFileExtensionsCode = '';
$fileExtensionsAndMediaType = array();

foreach ($mediaTypesAndFileExtensions as $mediaType => $fileExtensions) {
	$mediaTypesToFileExtensionsCode .= "\t\t'$mediaType' => array('" . implode("', '", $fileExtensions) . "'),\n";
	foreach ($fileExtensions as $fileExtension) {
		$fileExtensionsAndMediaType[$fileExtension] = $mediaType;
	}
}

ksort($fileExtensionsAndMediaType);

$fileExtensionsToMediaTypeCode = '';
foreach ($fileExtensionsAndMediaType as $fileExtension => $mediaType) {
	$fileExtensionsToMediaTypeCode .= "\t\t'$fileExtension' => '$mediaType',\n";
}

$classCode = file_get_contents($mediaTypesClassPathAndFilename);
$classCode = preg_replace('/(extensionToMediaType = array\(\n)([^\)]+)(\t\);)/', '$1' . $fileExtensionsToMediaTypeCode . "\t);", $classCode);
$classCode = preg_replace('/(mediaTypeToFileExtension = array\(\n)([^\;]+)(;)/', '$1' . $mediaTypesToFileExtensionsCode . "\t);", $classCode);
file_put_contents($mediaTypesClassPathAndFilename, $classCode);

?>