<?php
namespace Neos\Flow\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Cli\CommandController;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Utility\SchemaValidator;
use Symfony\Component\Yaml\Yaml;
use Neos\Utility\Files;

/**
 * Configuration command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class SchemaCommandController extends CommandController
{

    /**
     * @Flow\Inject(lazy = false)
     * @var SchemaValidator
     */
    protected $schemaValidator;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Validate the given configurationfile againt a schema file
     *
     * @param string $configurationFile path to the validated configuration file
     * @param string $schemaFile path to the schema file
     * @param boolean $verbose if true, output more verbose information on the schema files which were used
     * @return void
     */
    public function validateCommand(string $configurationFile = null, string $schemaFile = 'resource://Neos.Flow/Private/Schema/Schema.schema.yaml', bool $verbose = false)
    {
        $this->outputLine('Validating <b>' . $configurationFile . '</b> with schema  <b>' . $schemaFile . '</b>');
        $this->outputLine();

        $schema = Yaml::parseFile($schemaFile);

        if (is_null($configurationFile)) {
            $result = new Result();
            $activePackages = $this->packageManager->getAvailablePackages();
            foreach ($activePackages as $package) {
                $packageKey = $package->getPackageKey();
                $packageSchemaPath = Files::concatenatePaths([$package->getResourcesPath(), 'Private/Schema']);
                if (is_dir($packageSchemaPath) && $packageKey !== 'Neos.Utility.Schema') {
                    foreach (Files::getRecursiveDirectoryGenerator($packageSchemaPath, '.schema.yaml') as $schemaFile) {
                        $configuration = Yaml::parseFile($schemaFile);
                        $schemaPath = str_replace(FLOW_PATH_ROOT, '', $schemaFile);
                        $configurationResult = $this->schemaValidator->validate($configuration, $schema);
                        $result->forProperty($schemaPath)->merge($configurationResult);
                    }
                }
            }
        } else {
            $configuration = Yaml::parseFile($configurationFile);
            $result = $this->schemaValidator->validate($configuration, $schema);
        }

        if ($verbose) {
            $this->outputLine();
            if ($result->hasNotices()) {
                $notices = $result->getFlattenedNotices();
                $this->outputLine('<b>%d notices:</b>', [count($notices)]);
                /** @var Notice $notice */
                foreach ($notices as $path => $pathNotices) {
                    foreach ($pathNotices as $notice) {
                        $this->outputLine(' - %s -> %s', [$path, $notice->render()]);
                    }
                }
                $this->outputLine();
            }
        }

        if ($result->hasErrors()) {
            $errors = $result->getFlattenedErrors();
            $this->outputLine('<b>%d errors were found:</b>', [count($errors)]);
            /** @var Error $error */
            foreach ($errors as $path => $pathErrors) {
                foreach ($pathErrors as $error) {
                    $this->outputLine(' - %s -> %s', [$path, $error->render()]);
                }
            }
            $this->quit(1);
        } else {
            $this->outputLine('<b>All Valid!</b>');
        }
    }

    /**
     * Validate the given configurationfile againt a schema file
     *
     * @param string $configurationFile path to the validated configuration file
     * @param string $schemaFile path to the schema file
     * @param boolean $verbose if true, output more verbose information on the schema files which were used
     * @return void
     */
    public function validateSchemaCommand(string $configurationFile, string $schemaFile = 'resource://Neos.Flow/Private/Schema/Schema.schema.yaml', bool $verbose = false)
    {
        $this->outputLine('Validating <b>' . $configurationFile . '</b> with schema  <b>' . $schemaFile . '</b>');
        $this->outputLine();

        $configuration = Yaml::parseFile($configurationFile);
        $schema = Yaml::parseFile($schemaFile);

        $result = $this->schemaValidator->validate($configuration, $schema);

        if ($verbose) {
            $this->outputLine();
            if ($result->hasNotices()) {
                $notices = $result->getFlattenedNotices();
                $this->outputLine('<b>%d notices:</b>', [count($notices)]);
                /** @var Notice $notice */
                foreach ($notices as $path => $pathNotices) {
                    foreach ($pathNotices as $notice) {
                        $this->outputLine(' - %s -> %s', [$path, $notice->render()]);
                    }
                }
                $this->outputLine();
            }
        }

        if ($result->hasErrors()) {
            $errors = $result->getFlattenedErrors();
            $this->outputLine('<b>%d errors were found:</b>', [count($errors)]);
            /** @var Error $error */
            foreach ($errors as $path => $pathErrors) {
                foreach ($pathErrors as $error) {
                    $this->outputLine(' - %s', [$error->render()]);
                }
            }
            $this->quit(1);
        } else {
            $this->outputLine('<b>All Valid!</b>');
        }
    }
}
