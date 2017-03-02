<?php
namespace Neos\Kickstarter\Service;

/*
 * This file is part of the Neos.Kickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\Flow\Core\ClassLoader;
use Neos\Flow\Package\PackageInterface;
use Neos\Utility\Files;

/**
 * Service for the Kickstart generator
 *
 */
class GeneratorService
{
    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var \Neos\Flow\Package\PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var \Neos\Kickstarter\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * @var \Neos\Flow\Reflection\ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $generatedFiles = array();

    /**
     * Generate a controller with the given name for the given package
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $subpackage An optional subpackage name
     * @param string $controllerName The name of the new controller
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateActionController($packageKey, $subpackage, $controllerName, $overwrite = false)
    {
        list($baseNamespace, $namespaceEntryPath) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $controllerName = ucfirst($controllerName);
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Controller/ActionControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = trim($baseNamespace, '\\');
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = Files::concatenatePaths([$namespaceEntryPath, $subpackagePath, 'Controller']) . '/';
        $targetPathAndFilename = $controllerPath . $controllerFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate an Action Controller with pre-made CRUD methods
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $subpackage An optional subpackage name
     * @param string $controllerName The name of the new controller
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateCrudController($packageKey, $subpackage, $controllerName, $overwrite = false)
    {
        list($baseNamespace, $namespaceEntryPath) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $controllerName = ucfirst($controllerName);
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Controller/CrudControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = trim($baseNamespace, '\\');
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;
        $contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
        $contextVariables['repositoryClassName'] = '\\' . trim($baseNamespace, '\\') . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
        $contextVariables['modelFullClassName'] = '\\' . trim($baseNamespace, '\\') . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
        $contextVariables['modelClassName'] = ucfirst($contextVariables['modelName']);

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = Files::concatenatePaths([$namespaceEntryPath, $subpackagePath, 'Controller']) . '/';
        $targetPathAndFilename = $controllerPath . $controllerFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a command controller with the given name for the given package
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $controllerName The name of the new controller
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateCommandController($packageKey, $controllerName, $overwrite = false)
    {
        list($baseNamespace, $namespaceEntryPath) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $controllerName = ucfirst($controllerName) . 'Command';
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Controller/CommandControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = trim($baseNamespace, '\\');
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = Files::concatenatePaths([$namespaceEntryPath, 'Command']) . '/';
        $targetPathAndFilename = $controllerPath . $controllerFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a view with the given name for the given package and controller
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $subpackage An optional subpackage name
     * @param string $controllerName The name of the new controller
     * @param string $viewName The name of the view
     * @param string $templateName The name of the view
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateView($packageKey, $subpackage, $controllerName, $viewName, $templateName, $overwrite = false)
    {
        list($baseNamespace) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $viewName = ucfirst($viewName);

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/View/' . $templateName . 'Template.html';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerName'] = $controllerName;
        $contextVariables['viewName'] = $viewName;
        $contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
        $contextVariables['repositoryClassName'] = '\\' . trim($baseNamespace, '\\') . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
        $contextVariables['modelFullClassName'] = '\\' . trim($baseNamespace, '\\') . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
        $contextVariables['modelClassName'] = ucfirst($contextVariables['modelName']);

        $modelClassSchema = $this->reflectionService->getClassSchema($contextVariables['modelFullClassName']);
        if ($modelClassSchema !== null) {
            $contextVariables['properties'] = $modelClassSchema->getProperties();
            if (isset($contextVariables['properties']['Persistence_Object_Identifier'])) {
                unset($contextVariables['properties']['Persistence_Object_Identifier']);
            }
        }

        if (!isset($contextVariables['properties']) || $contextVariables['properties'] === array()) {
            $contextVariables['properties'] = array('name' => array('type' => 'string'));
        }

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
        $viewFilename = $viewName . '.html';
        $viewPath = 'resource://' . $packageKey . '/Private/Templates/' . $subpackagePath . $controllerName . '/';
        $targetPathAndFilename = $viewPath . $viewFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a default layout
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $layoutName The name of the layout
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateLayout($packageKey, $layoutName, $overwrite = false)
    {
        $layoutName = ucfirst($layoutName);

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/View/' . $layoutName . 'Layout.html';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $layoutFilename = $layoutName . '.html';
        $viewPath = 'resource://' . $packageKey . '/Private/Layouts/';
        $targetPathAndFilename = $viewPath . $layoutFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a model for the package with the given model name and fields
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $modelName The name of the new model
     * @param array $fieldDefinitions The field definitions
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateModel($packageKey, $modelName, array $fieldDefinitions, $overwrite = false)
    {
        list($baseNamespace, $namespaceEntryPath) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $modelName = ucfirst($modelName);
        $namespace = trim($baseNamespace, '\\') . '\\Domain\\Model';
        $fieldDefinitions = $this->normalizeFieldDefinitions($fieldDefinitions, $namespace);

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Model/EntityTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['fieldDefinitions'] = $fieldDefinitions;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $modelFilename = $modelName . '.php';
        $modelPath = $namespaceEntryPath . '/Domain/Model/';
        $targetPathAndFilename = $modelPath . $modelFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        $this->generateTestsForModel($packageKey, $modelName, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a dummy testcase for a model for the package with the given model name
     *
     * @param string $packageKey The package key of the controller's package
     * @param string $modelName The name of the new model fpr which to generate the test
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateTestsForModel($packageKey, $modelName, $overwrite = false)
    {
        list($baseNamespace) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $testName = ucfirst($modelName) . 'Test';
        $namespace = trim($baseNamespace, '\\') . '\\Tests\\Unit\\Domain\\Model';

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Tests/Unit/Model/EntityTestTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['testName'] = $testName;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $testFilename = $testName . '.php';
        $testPath = $this->packageManager->getPackage($packageKey)->getPackagePath() . PackageInterface::DIRECTORY_TESTS_UNIT . 'Domain/Model/';
        $targetPathAndFilename = $testPath . $testFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a repository for a model given a model name and package key
     *
     * @param string $packageKey The package key
     * @param string $modelName The name of the model
     * @param boolean $overwrite Overwrite any existing files?
     * @return array An array of generated filenames
     */
    public function generateRepository($packageKey, $modelName, $overwrite = false)
    {
        list($baseNamespace, $namespaceEntryPath) = $this->getPrimaryNamespaceAndEntryPath($this->packageManager->getPackage($packageKey));
        $modelName = ucfirst($modelName);
        $repositoryClassName = $modelName . 'Repository';
        $namespace = trim($baseNamespace, '\\') . '\\Domain\\Repository';

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Repository/RepositoryTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['repositoryClassName'] = $repositoryClassName;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $repositoryFilename = $repositoryClassName . '.php';
        $repositoryPath = Files::concatenatePaths([$namespaceEntryPath, 'Domain/Repository']) . '/';
        $targetPathAndFilename = $repositoryPath . $repositoryFilename;

        $this->generateFile($targetPathAndFilename, $fileContent, $overwrite);

        return $this->generatedFiles;
    }

    /**
     * Generate a documentation skeleton for the package key
     *
     * @param string $packageKey The package key
     * @return array An array of generated filenames
     */
    public function generateDocumentation($packageKey)
    {
        $documentationPath = Files::concatenatePaths([$this->packageManager->getPackage($packageKey)->getPackagePath(), 'Documentation']);
        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Documentation/conf.py';
        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);
        $targetPathAndFilename = $documentationPath . '/conf.py';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Documentation/Makefile';
        $fileContent = file_get_contents($templatePathAndFilename);
        $targetPathAndFilename = $documentationPath . '/Makefile';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $templatePathAndFilename = 'resource://Neos.Kickstarter/Private/Generator/Documentation/index.rst';
        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);
        $targetPathAndFilename = $documentationPath . '/index.rst';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $targetPathAndFilename = $documentationPath . '/_build/.gitignore';
        $this->generateFile($targetPathAndFilename, '*' . chr(10) . '!.gitignore' . chr(10));
        $targetPathAndFilename = $documentationPath . '/_static/.gitignore';
        $this->generateFile($targetPathAndFilename, '*' . chr(10) . '!.gitignore' . chr(10));
        $targetPathAndFilename = $documentationPath . '/_templates/.gitignore';
        $this->generateFile($targetPathAndFilename, '*' . chr(10) . '!.gitignore' . chr(10));

        return $this->generatedFiles;
    }

    /**
     * Normalize types and prefix types with namespaces
     *
     * @param array $fieldDefinitions The field definitions
     * @param string $namespace The namespace
     * @return array The normalized and type converted field definitions
     */
    protected function normalizeFieldDefinitions(array $fieldDefinitions, $namespace = '')
    {
        foreach ($fieldDefinitions as &$fieldDefinition) {
            if ($fieldDefinition['type'] == 'bool') {
                $fieldDefinition['type'] = 'boolean';
            } elseif ($fieldDefinition['type'] == 'int') {
                $fieldDefinition['type'] = 'integer';
            } elseif (preg_match('/^[A-Z]/', $fieldDefinition['type'])) {
                if (class_exists($fieldDefinition['type'])) {
                    $fieldDefinition['type'] = '\\' . $fieldDefinition['type'];
                } else {
                    $fieldDefinition['type'] = '\\' . $namespace . '\\' . $fieldDefinition['type'];
                }
            }
        }
        return $fieldDefinitions;
    }

    /**
     * Generate a file with the given content and add it to the
     * generated files
     *
     * @param string $targetPathAndFilename
     * @param string $fileContent
     * @param boolean $force
     * @return void
     */
    protected function generateFile($targetPathAndFilename, $fileContent, $force = false)
    {
        if (!is_dir(dirname($targetPathAndFilename))) {
            \Neos\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
        }

        if (substr($targetPathAndFilename, 0, 11) === 'resource://') {
            list($packageKey, $resourcePath) = explode('/', substr($targetPathAndFilename, 11), 2);
            $relativeTargetPathAndFilename = $packageKey . '/Resources/' . $resourcePath;
        } elseif (strpos($targetPathAndFilename, 'Tests') !== false) {
            $relativeTargetPathAndFilename = substr($targetPathAndFilename, strrpos(substr($targetPathAndFilename, 0, strpos($targetPathAndFilename, 'Tests/') - 1), '/') + 1);
        } else {
            $relativeTargetPathAndFilename = substr($targetPathAndFilename, strrpos(substr($targetPathAndFilename, 0, strpos($targetPathAndFilename, 'Classes/') - 1), '/') + 1);
        }

        if (!file_exists($targetPathAndFilename) || $force === true) {
            file_put_contents($targetPathAndFilename, $fileContent);
            $this->generatedFiles[] = 'Created .../' . $relativeTargetPathAndFilename;
        } else {
            $this->generatedFiles[] = 'Omitted .../' . $relativeTargetPathAndFilename;
        }
    }

    /**
     * Render the given template file with the given variables
     *
     * @param string $templatePathAndFilename
     * @param array $contextVariables
     * @return string
     * @throws \Neos\FluidAdaptor\Core\Exception
     */
    protected function renderTemplate($templatePathAndFilename, array $contextVariables)
    {
        $standaloneView = new StandaloneView();
        $standaloneView->setTemplatePathAndFilename($templatePathAndFilename);
        $standaloneView->assignMultiple($contextVariables);
        return $standaloneView->render();
    }

    /**
     * @param PackageInterface $package
     * @return array
     */
    protected function getPrimaryNamespaceAndEntryPath(PackageInterface $package)
    {
        $autoloadConfigurations = $package->getComposerManifest('autoload');

        $firstAutoloadType = null;
        $firstAutoloadConfiguration = null;
        foreach ($autoloadConfigurations as $autoloadType => $autoloadConfiguration) {
            if (ClassLoader::isAutoloadTypeWithPredictableClassPath($autoloadType)) {
                $firstAutoloadType = $autoloadType;
                $firstAutoloadConfiguration = $autoloadConfiguration;
                break;
            }
        }

        $autoloadPaths = reset($firstAutoloadConfiguration);
        $firstAutoloadPath = is_array($autoloadPaths) ? reset($autoloadPaths) : $autoloadPaths;
        $namespace = key($firstAutoloadConfiguration);
        $autoloadPathPostfix = '';
        if ($firstAutoloadType === ClassLoader::MAPPING_TYPE_PSR0) {
            $autoloadPathPostfix = str_replace('\\', '/', trim($namespace, '\\'));
        }

        return [
            $namespace,
            Files::concatenatePaths([$package->getPackagePath(), $firstAutoloadPath, $autoloadPathPostfix]),
            $firstAutoloadType,
        ];
    }
}
