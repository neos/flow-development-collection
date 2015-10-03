<?php
namespace TYPO3\Kickstart\Service;

/*
 * This file is part of the TYPO3.Kickstart package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Service for the Kickstart generator
 *
 */
class GeneratorService
{
    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var \TYPO3\Flow\Package\PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var \TYPO3\Fluid\Core\Parser\TemplateParser
     * @Flow\Inject
     */
    protected $templateParser;

    /**
     * @var \TYPO3\Kickstart\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
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
        $controllerName = ucfirst($controllerName);
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/ActionControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . $subpackagePath . 'Controller/';
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
        $controllerName = ucfirst($controllerName);
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/CrudControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;
        $contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
        $contextVariables['repositoryClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
        $contextVariables['modelFullClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
        $contextVariables['modelClassName'] = ucfirst($contextVariables['modelName']);

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . $subpackagePath . 'Controller/';
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
        $controllerName = ucfirst($controllerName) . 'Command';
        $controllerClassName = $controllerName . 'Controller';

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Controller/CommandControllerTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['packageNamespace'] = str_replace('.', '\\', $packageKey);
        $contextVariables['controllerClassName'] = $controllerClassName;
        $contextVariables['controllerName'] = $controllerName;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $controllerFilename = $controllerClassName . '.php';
        $controllerPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Command/';
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
        $viewName = ucfirst($viewName);

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/View/' . $templateName . 'Template.html';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['subpackage'] = $subpackage;
        $contextVariables['isInSubpackage'] = ($subpackage != '');
        $contextVariables['controllerName'] = $controllerName;
        $contextVariables['viewName'] = $viewName;
        $contextVariables['modelName'] = strtolower($controllerName[0]) . substr($controllerName, 1);
        $contextVariables['repositoryClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Repository\\' . $controllerName . 'Repository';
        $contextVariables['modelFullClassName'] = '\\' . str_replace('.', '\\', $packageKey) . ($subpackage != '' ? '\\' . $subpackage : '') . '\Domain\Model\\' . $controllerName;
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

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/View/' . $layoutName . 'Layout.html';

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
        $modelName = ucfirst($modelName);
        $namespace = str_replace('.', '\\', $packageKey) . '\\Domain\\Model';
        $fieldDefinitions = $this->normalizeFieldDefinitions($fieldDefinitions, $namespace);

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Model/EntityTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['fieldDefinitions'] = $fieldDefinitions;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $modelFilename = $modelName . '.php';
        $modelPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Domain/Model/';
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
        $testName = ucfirst($modelName) . 'Test';
        $namespace = str_replace('.', '\\', $packageKey) . '\\Tests\\Unit\\Domain\\Model';

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Tests/Unit/Model/EntityTestTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['testName'] = $testName;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $testFilename = $testName . '.php';
        $testPath = $this->packageManager->getPackage($packageKey)->getPackagePath() . \TYPO3\Flow\Package\PackageInterface::DIRECTORY_TESTS_UNIT . 'Domain/Model/';
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
        $modelName = ucfirst($modelName);
        $repositoryClassName = $modelName . 'Repository';
        $namespace = str_replace('.', '\\', $packageKey) . '\\Domain\\Repository';

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Repository/RepositoryTemplate.php.tmpl';

        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;
        $contextVariables['modelName'] = $modelName;
        $contextVariables['repositoryClassName'] = $repositoryClassName;
        $contextVariables['namespace'] = $namespace;

        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

        $repositoryFilename = $repositoryClassName . '.php';
        $repositoryPath = $this->packageManager->getPackage($packageKey)->getClassesNamespaceEntryPath() . 'Domain/Repository/';
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
        $contextVariables = array();
        $contextVariables['packageKey'] = $packageKey;

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Documentation/conf.py';
        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);
        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . 'conf.py';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Documentation/Makefile';
        $fileContent = file_get_contents($templatePathAndFilename);
        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . 'Makefile';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $templatePathAndFilename = 'resource://TYPO3.Kickstart/Private/Generator/Documentation/index.rst';
        $fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);
        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . 'index.rst';
        $this->generateFile($targetPathAndFilename, $fileContent);

        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . '_build/.gitignore';
        $this->generateFile($targetPathAndFilename, '*' . chr(10) . '!.gitignore' . chr(10));
        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . '_static/.gitignore';
        $this->generateFile($targetPathAndFilename, '*' . chr(10) . '!.gitignore' . chr(10));
        $targetPathAndFilename = $this->packageManager->getPackage($packageKey)->getDocumentationPath() . '_templates/.gitignore';
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
            \TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
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
     * @throws \TYPO3\Fluid\Core\Exception
     */
    protected function renderTemplate($templatePathAndFilename, array $contextVariables)
    {
        $templateSource = \TYPO3\Flow\Utility\Files::getFileContents($templatePathAndFilename, FILE_TEXT);
        if ($templateSource === false) {
            throw new \TYPO3\Fluid\Core\Exception('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
        }
        $parsedTemplate = $this->templateParser->parse($templateSource);

        $renderingContext = $this->buildRenderingContext($contextVariables);

        return $parsedTemplate->render($renderingContext);
    }

    /**
     * Build the rendering context
     *
     * @param array $contextVariables
     * @return \TYPO3\Fluid\Core\Rendering\RenderingContext
     */
    protected function buildRenderingContext(array $contextVariables)
    {
        $renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();

        $renderingContext->injectTemplateVariableContainer(new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer($contextVariables));
        $renderingContext->injectViewHelperVariableContainer(new \TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer());

        return $renderingContext;
    }
}
