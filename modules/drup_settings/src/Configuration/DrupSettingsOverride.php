<?php

namespace Drupal\drup_settings\Configuration;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\drup_settings\DrupSettings;

/**
 * Class DrupSettingsOverride.
 *
 * @package Drupal\drup_settings
 */
class DrupSettingsOverride implements ConfigFactoryOverrideInterface {
    
    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;
    
    /**
     * Constructs a DomainSourcePathProcessor object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The module handler service.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory) {
        $this->configFactory = $config_factory;
    }
    
    /**
     * Returns config overrides.
     *
     * @param array $names
     *   A list of configuration names that are being loaded.
     *
     * @return array
     *   An array keyed by configuration name of override data. Override data
     *   contains a nested array structure of overrides.
     */
    public function loadOverrides($names) {
        $overrides = array();
    
        if (in_array('system.site', $names)) {
            $drupSettings = new DrupSettings();

            $overrides['system.site'] = [
                'name' => $drupSettings->getValue('site_name'),
                'slogan' => $drupSettings->getValue('site_slogan')
            ];
        }
        return $overrides;
    }
    
    /**
     * The string to append to the configuration static cache name.
     *
     * @return string
     *   A string to append to the configuration static cache name.
     */
    public function getCacheSuffix() {
        return 'DrupSettingsConfigurationOverrider';
    }
    
    /**
     * Gets the cacheability metadata associated with the config factory override.
     *
     * @param string $name
     *   The name of the configuration override to get metadata for.
     *
     * @return \Drupal\Core\Cache\CacheableMetadata
     *   A cacheable metadata object.
     */
    public function getCacheableMetadata($name) {
        return new CacheableMetadata();
    }
    
    /**
     * Creates a configuration object for use during install and synchronization.
     *
     * If the overrider stores its overrides in configuration collections then
     * it can have its own implementation of
     * \Drupal\Core\Config\StorableConfigBase. Configuration overriders can link
     * themselves to a configuration collection by listening to the
     * \Drupal\Core\Config\ConfigEvents::COLLECTION_INFO event and adding the
     * collections they are responsible for. Doing this will allow installation
     * and synchronization to use the overrider's implementation of
     * StorableConfigBase.
     *
     * @see \Drupal\Core\Config\ConfigCollectionInfo
     * @see \Drupal\Core\Config\ConfigImporter::importConfig()
     * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
     *
     * @param string $name
     *   The configuration object name.
     * @param string $collection
     *   The configuration collection.
     *
     * @return \Drupal\Core\Config\StorableConfigBase
     *   The configuration object for the provided name and collection.
     */
    public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
        return null;
    }
}
