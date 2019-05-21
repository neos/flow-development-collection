<?php
namespace Neos\Flow\Core\Migrations;

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
use Neos\Flow\Log\PsrLoggerFactory;

/**
 * Adjust "Settings.yaml" to new PSR-3 logging settings (see https://github.com/neos/flow-development-collection/pull/1574)
 */
class Version20190515215000 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20190515215000';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (array &$configuration) {
                $this->processLogSettings($configuration);
            },
            true
        );
    }

    /**
     * Adjust Neos.Flow.log.* syntax & nesting
     *
     * @param array $configuration
     * @return void
     */
    public function processLogSettings(array &$configuration): void
    {
        if (!isset($configuration['Neos']['Flow']['log']) || !is_array($configuration['Neos']['Flow']['log'])) {
            return;
        }
        $logConfiguration = &$configuration['Neos']['Flow']['log'];

        if (isset($logConfiguration['psr3']['loggerFactory']) && $logConfiguration['psr3']['loggerFactory'] === 'legacy') {
            $logConfiguration['psr3']['loggerFactory'] = PsrLoggerFactory::class;
        }
        foreach ($logConfiguration as $loggerName => $options) {
            if ($loggerName === 'psr3') {
                continue;
            }

            if (isset($options['logger'])) {
                unset($options['logger']);
            }
            if (isset($options['backend'])) {
                $options['class'] = $options['backend'];
                unset($options['backend']);
            }
            if (isset($options['backendOptions'])) {
                $options['options'] = $options['backendOptions'];
                unset($options['backendOptions']);
            }

            $logConfiguration['psr3'][PsrLoggerFactory::class][$loggerName]['default'] = $options;
            unset($logConfiguration[$loggerName]);

            $this->showNote(sprintf('Adjusted configuration of the "%s" logger settings.', $loggerName));
        }
    }
}
