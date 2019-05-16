<?php

namespace Drupal\drup_settings;

use Drupal\language\Config\LanguageConfigOverride;
use Drupal\language\ConfigurableLanguageManager;


/**
 * Class DrupSettings
 *
 * @package Drupal\drup_settings
 */
class DrupSettings {

    /**
     * Nom de la configuration
     *
     * @var string
     */
    protected static $configName = 'drup.settings';

    /**
     * @var string
     */
    protected static $languageNeutral = 'und';

    /**
     * @var ConfigurableLanguageManager
     */
    protected $languageManager;

    /**
     * @var string
     */
    protected $languageId;

    /**
     * @var LanguageConfigOverride
     */
    protected $config;

    /**
     * Retourne le nom de la configuration
     *
     * @return string
     */
    public static function getConfigName() {
        return self::$configName;
    }

    /**
     * DrupSettings constructor.
     *
     * @param null $languageId
     */
    public function __construct($languageId = null) {
        $this->languageManager = \Drupal::languageManager();
        $this->setLanguage($languageId);
    }

    /**
     * Applique la config selon la langue courante
     */
    protected function setConfig() {
        $config = $this->languageManager->getLanguageConfigOverride($this->languageId, self::getConfigName());

        if ($config instanceof LanguageConfigOverride) {
            $this->config = $config;
        }
    }

    /**
     * Retourne la configuration de DrupSettings contextualisée par la langue courante
     *
     * @return bool|LanguageConfigOverride
     */
    protected function getConfig() {
        return $this->config;
    }


    /**
     * Applique une langue
     *
     * @param null $languageId
     */
    public function setLanguage($languageId = null) {
        if ($languageId === null) {
            $languageId = $this->languageManager->getCurrentLanguage()->getId();
        }
        $this->languageId = $languageId;
        $this->setConfig();
    }

    /**
     * Applique une langue à la configuration
     */
    public function setNeutralLang() {
        $this->setLanguage(self::$languageNeutral);
    }

    /**
     * Formatte le nom d'une config
     *
     * @param $variable
     *
     * @return mixed
     */
    public function getName($variable) {
        return $variable;
    }

    /**
     * Récupère la valeur d'une config
     *
     * @param $variable
     *
     * @return mixed
     */
    public function getValue($variable) {
        return $this->getConfig()->get($this->getName($variable));
    }

    /**
     * Recherche dans la config toutes les variables commençant par un motif
     *
     * @param string $search  Motif à cherche (exemple : contact)
     * @param bool $trimSearch Enlève la valeur de $search des indexes du tableau de résultats (ex : contact_phone => phone)
     *
     * @return array
     */
    public function searchValues($search, $trimSearch = true) {
        $values = [];
        $searchValue = $this->getName($search);

        foreach ($this->getConfig()->get() as $key => $value) {
            if (strpos($key, $searchValue) === 0) {
                if ($trimSearch === true) {
                    $key = str_replace($searchValue, '', $key);
                    $key = trim($key, '_');
                }
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Register prefixed variable value by name
     * @param $variable
     * @param $value
     */
    public function set($variable, $value) {
        $this->getConfig()->set($this->getName($variable), $value);
        $this->save();
    }

    /**
     * Save system.config
     */
    public function save() {
        $this->config->save();
    }
}
