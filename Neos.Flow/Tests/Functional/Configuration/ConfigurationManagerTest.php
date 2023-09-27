<?php
namespace Neos\Flow\Tests\Functional\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Utility\Environment;

class ConfigurationManagerTest extends FunctionalTestCase
{

    /**
     * @test
     */
    public function customInstanceCanBeCreated(): void
    {
        $applicationContext = new ApplicationContext('Testing/SubContext');
        $configurationManager = new ConfigurationManager($applicationContext);
        $environment = new Environment($applicationContext);
        $environment->setTemporaryDirectoryBase(FLOW_PATH_TEMPORARY_BASE);
        $configurationManager->setTemporaryDirectoryPath($environment->getPathToTemporaryDirectory());
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, new SettingsLoader(new YamlSource()));
        self::assertSame('Testing/SubContext', $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.core.context'));
    }
}
