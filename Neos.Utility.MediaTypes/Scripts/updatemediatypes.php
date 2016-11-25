<?php
/*
 * This file is part of the Neos.Utility.MediaTypes package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * This script is used to update the available Internet Media Types in
 * Utility\MediaTypes from the mime.types file in the Apache HTTPD SVN
 * repository.
 *
 * It is a tool for use by the Flow maintainers and can safely be ignored
 * by Flow users.
 */

$mediaTypesClassPathAndFilename = __DIR__ . '/../Classes/MediaTypes.php';

$rawList = file_get_contents('http://svn.apache.org/viewvc/httpd/httpd/branches/2.4.x/docs/conf/mime.types?revision=HEAD&view=co');

$mediaTypesAndFileExtensions = array();

foreach (explode("\n", $rawList) as $line) {
    preg_match_all('/^([a-z][^\s]+)\s+(.+)$/', $line, $matches, PREG_SET_ORDER);
    if (count($matches) === 1) {
        $mediaTypesAndFileExtensions[$matches[0][1]] = preg_split('/\s+/', $matches[0][2]);
    }
}

ksort($mediaTypesAndFileExtensions);

$mediaTypesToFileExtensionsCode = '';
$fileExtensionsAndMediaType = array();

foreach ($mediaTypesAndFileExtensions as $mediaType => $fileExtensions) {
    $mediaTypesToFileExtensionsCode .= "        '$mediaType' => ['" . implode("', '", $fileExtensions) . "'],\n";
    foreach ($fileExtensions as $fileExtension) {
        $fileExtensionsAndMediaType[$fileExtension] = $mediaType;
    }
}

ksort($fileExtensionsAndMediaType);

$fileExtensionsToMediaTypeCode = '';
foreach ($fileExtensionsAndMediaType as $fileExtension => $mediaType) {
    $fileExtensionsToMediaTypeCode .= "        '$fileExtension' => '$mediaType',\n";
}

$classCode = file_get_contents($mediaTypesClassPathAndFilename);
$classCode = preg_replace('/(extensionToMediaType = \[\n)([^\)]+)(\t\];)/', '$1' . $fileExtensionsToMediaTypeCode . "    ];", $classCode);
$classCode = preg_replace('/(mediaTypeToFileExtension = \[\n)([^\;]+)(;)/', '$1' . $mediaTypesToFileExtensionsCode . "    ];", $classCode);
file_put_contents($mediaTypesClassPathAndFilename, $classCode);
