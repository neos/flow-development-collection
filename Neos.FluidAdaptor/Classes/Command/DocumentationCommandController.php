<?php
declare(strict_types=1);

namespace Neos\FluidAdaptor\Command;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\FluidAdaptor\Service;

/**
 * Command controller for Fluid documentation rendering
 *
 * @Flow\Scope("singleton")
 */
class DocumentationCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var \Neos\FluidAdaptor\Service\XsdGenerator
     */
    protected $xsdGenerator;

    /**
     * Generate Fluid ViewHelper XSD Schema
     *
     * Generates Schema documentation (XSD) for your ViewHelpers, preparing the
     * file to be placed online and used by any XSD-aware editor.
     * After creating the XSD file, reference it in your IDE and import the namespace
     * in your Fluid template by adding the xmlns:* attribute(s):
     * <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="https://neos.io/ns/Neos/Neos/ViewHelpers" ...>
     *
     * @param string $phpNamespace Namespace of the Fluid ViewHelpers without leading backslash (for example 'Neos\FluidAdaptor\ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!
     * @param string $xsdNamespace Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "https://neos.io/ns/<php namespace>".
     * @param string $targetFile File path and name of the generated XSD schema. If not specified the schema will be output to standard output.
     * @param string $xsdDomain Domain used in the XSD schema (for example "http://yourdomain.org"). Defaults to "https://neos.io".
     * @return void
     */
    public function generateXsdCommand(string $phpNamespace, string $xsdNamespace = null, string $targetFile = null, string $xsdDomain = ''): void
    {
        $xsdDomain = trim($xsdDomain);
        $parsedDomain = parse_url($xsdDomain);
        if (empty($xsdDomain) || !isset($parsedDomain['host'], $parsedDomain['scheme'])) {
            $xsdDomain = 'https://neos.io';
        }
        if ($xsdNamespace === null) {
            $xsdNamespace = sprintf('%s/ns/%s', $xsdDomain, str_replace('\\', '/', $phpNamespace));
        }
        $xsdSchema = '';
        try {
            $xsdSchema = $this->xsdGenerator->generateXsd($phpNamespace, $xsdNamespace);
        } catch (Service\Exception $exception) {
            $this->outputLine('An error occurred while trying to generate the XSD schema:');
            $this->outputLine('%s', [$exception->getMessage()]);
            $this->quit(1);
        }
        if ($targetFile === null) {
            $this->output($xsdSchema);
        } else {
            file_put_contents($targetFile, $xsdSchema);
        }
    }
}
