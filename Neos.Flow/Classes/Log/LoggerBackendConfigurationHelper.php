<?php
namespace Neos\Flow\Log;

/**
 * Helps to streamline the configuration for the default logger.
 */
class LoggerBackendConfigurationHelper
{
    /**
     * @var array
     */
    protected $legacyConfiguration;

    /**
     * LoggerBackendConfigurationHelper constructor.
     *
     * @param array $logConfiguration
     */
    public function __construct(array $logConfiguration)
    {
        $this->legacyConfiguration = $logConfiguration;
    }

    /**
     * Normalize a backend configuration to a unified format.
     *
     * @return array
     */
    public function getNormalizedLegacyConfiguration(): array
    {
        $normalizedConfiguration = [];
        foreach ($this->legacyConfiguration as $logIdentifier => $configuration) {
            // Skip everything that is not an actual log configuration.
            if (!isset($configuration['backend'])) {
                continue;
            }

            $backendObjectNames = $configuration['backend'];
            $backendOptions = $configuration['backendOptions'] ?? [];
            $normalizedConfiguration[$logIdentifier] = $this->mapLoggerConfiguration($backendObjectNames, $backendOptions);
        }

        return $normalizedConfiguration;
    }

    /**
     * @param mixed $backendObjectNames
     * @param array $backendOptions
     * @return array
     */
    protected function mapLoggerConfiguration($backendObjectNames, array $backendOptions): array
    {
        if (!is_array($backendObjectNames)) {
            return [$this->mapBackendInformation($backendObjectNames, $backendOptions)];
        }

        $backends = [];
        foreach ($backendObjectNames as $i => $backendObjectName) {
            if (isset($backendOptions[$i])) {
                $backends[] = $this->mapBackendInformation($backendObjectName, $backendOptions[$i]);
            }
        }

        return $backends;
    }

    /**
     * Map a backend object name and it's options into an array with defined keys.
     *
     * @param string $backendObjectName
     * @param array $backendOptions
     * @return array
     */
    protected function mapBackendInformation(string $backendObjectName, array $backendOptions): array
    {
        return [
            'class' => $backendObjectName,
            'options' => $backendOptions
        ];
    }
}
