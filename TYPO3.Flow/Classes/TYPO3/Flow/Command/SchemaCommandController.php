<?php
namespace TYPO3\Flow\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Symfony\Component\Yaml\Yaml;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Configuration\ConfigurationSchemaValidator;
use TYPO3\Flow\Configuration\Exception\SchemaValidationException;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Error\Notice;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\SchemaValidator;

/**
 * Configuration command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class SchemaCommandController extends CommandController
{

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var SchemaValidator
     */
    protected $schemaValidator;

    /**
     * Validate the given configurationfile againt a schema file
     *
     * @param string $configurationFile path to the validated configuration file
     * @param string $schemaFile path to the schema file
     * @param boolean $verbose if TRUE, output more verbose information on the schema files which were used
     * @return void
     */
    public function validateCommand($configurationFile, $schemaFile = 'resource://Neos.Utility.Schema/Private/Schema/Schema.schema.yaml', $verbose = false)
    {
        $this->outputLine('Validating <b>' . $configurationFile . '</b> with schema  <b>' . $schemaFile . '</b>');
        $this->outputLine();

        $configuration = \Symfony\Component\Yaml\Yaml::parse($configurationFile);
        $schema = \Symfony\Component\Yaml\Yaml::parse($schemaFile);

        $result = $this->schemaValidator->validate($configuration, $schema);

        if ($verbose) {
            $this->outputLine();
            if ($result->hasNotices()) {
                $notices = $result->getFlattenedNotices();
                $this->outputLine('<b>%d notices:</b>', array(count($notices)));
                /** @var Notice $notice */
                foreach ($notices as $path => $pathNotices) {
                    foreach ($pathNotices as $notice) {
                        $this->outputLine(' - %s -> %s', array($path, $notice->render()));
                    }
                }
                $this->outputLine();
            }
        }

        if ($result->hasErrors()) {
            $errors = $result->getFlattenedErrors();
            $this->outputLine('<b>%d errors were found:</b>', array(count($errors)));
            /** @var Error $error */
            foreach ($errors as $path => $pathErrors) {
                foreach ($pathErrors as $error) {
                    $this->outputLine(' - %s', array($error->render()));
                }
            }
            $this->quit(1);
        } else {
            $this->outputLine('<b>All Valid!</b>');
        }
    }
}
