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
     * Nom de la configuration des variables
     *
     * @var string
     */
    protected static $configValuesName = 'drup.settings';

    /**
     * Nom de la configuration des contextes pour récupérer les variables
     *
     * @var string
     */
    protected static $configContextsName = 'drup.settings.contexts';

    /**
     * @var LanguageConfigOverride
     */
    protected $configValues;

    /**
     * @var \Drupal\Core\Config\Config
     */
    protected $configContexts;

    /**
     * Nom du contexte spécifiant qu'une variable est disponible pour toutes les langues
     *
     * @var string
     */
    public static $contextNeutral = 'und';

    /**
     * @var string
     */
    protected $context;

    /**
     * @var ConfigurableLanguageManager
     */
    protected $languageManager;

    /**
     * Retourne le nom de la configuration des variables
     *
     * @return string
     */
    public static function getConfigValuesName() {
        return self::$configValuesName;
    }

    /**
     * Retourne le nom de la configuration des contextes
     *
     * @return string
     */
    public static function getConfigContextsName() {
        return self::$configContextsName;
    }


    /**
     * DrupSettings constructor.
     *
     * @param null $context
     */
    public function __construct(string $context = null) {
        $this->configContexts = \Drupal::service('config.factory')->getEditable(self::getConfigContextsName());

        $this->languageManager = \Drupal::languageManager();
        $this->applyContext($context);
    }

    /**
     * Applique un contexte à l'ensemble de la classe, et réinstancie la config des variables en fonction
     *
     * @param string|null $context
     */
    public function applyContext(string $context = null) {
        if ($context === null) {
            $context = $this->languageManager->getCurrentLanguage()->getId();
        }
        $this->context = $context;

        if ($config = $this->getConfigValuesByContext($this->context)) {
            $this->configValues = $config;
        }
    }

    /**
     * Applique le contexte commun
     */
    public function applyNeutralContext() {
        $this->applyContext(self::$contextNeutral);
    }

    /**
     * Récupère une variable en fonction d'un contexte (celui par défaut de la variable ou forcé en spécifique)
     *
     * @param string $key           Clé de la variable
     * @param string|null $context  Force un contexte spécifique
     *
     * @return mixed
     */
    public function getValue(string $key, string $context = null) {
        $context = $this->getContext($key, $context);
        $config = ($context === $this->context) ? $this->getConfigValues() : $this->getConfigValuesByContext($context);

        return $config->get($this->formatKey($key));
    }

    /**
     * Recherche dans la config toutes les variables commençant par un motif
     *
     * @param string $pattern        Motif à cherche (exemple : contact)
     * @param bool $trimSearch       Enlève la valeur de $search des indexes du tableau de résultats (ex : contact_phone => phone)
     * @param string|null $context
     *
     * @return array
     */
    public function searchValues(string $pattern, $trimSearch = true, string $context = null) {
        $values = [];

        $pattern = $this->formatKey($pattern);

        $contexts = $this->getContexts();
        foreach ($contexts as $key => $variable) {
            if (strpos($key, $pattern) !== false) {
                $variableContext = $context === null ? $variable['context'] : $this->getContext($this->formatKey($key), $context);
                $value = $this->getValue($key, $variableContext);

                if ($trimSearch === true) {
                    $key = trim(str_replace($pattern, '', $key), '_');
                }
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * Enregistre une variable dans la config contextualisée
     *
     * @param string $key
     * @param $value
     * @param string|null $context
     */
    public function setValue(string $key, $value, string $context = null) {
        $context = $this->getContext($key, $context);
        $config = ($context === $this->context) ? $this->getConfigValues() : $this->getConfigValuesByContext($context);
        $config->set($this->formatKey($key), $value)->save();
    }


    /**
     * Enregistre le contexte par défaut de chaque variable de DrupSettings
     *
     * @param array $contexts
     *
     * @see \Drupal\drup_settings\Form\DrupSettingsForm
     */
    public function setContexts(array $contexts) {
        foreach ($contexts as $index => $context) {
            $this->configContexts->set($index, (array) $context);
        }
        $this->configContexts->save();
    }





    /**
     * Liste du contexte de chaque variable
     *
     * @return \Drupal\Core\Config\Config
     */
    protected function getContexts() {
        return $this->configContexts->get();
    }

    /**
     * Récupère le contexte par défaut d'une variable
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function getDefaultContext(string $key) {
        if ($data = $this->configContexts->get($key)) {
            return $data['context'];
        }

        return null;
    }

    /**
     * Récupère le contexte à appliquer localement
     *
     * @param string $key
     * @param string|null $context
     *
     * @return string|null
     */
    protected function getContext(string $key, string $context = null) {
        if ($context === null) {
            $context = $this->getDefaultContext($this->formatKey($key));
        }
        if ($context === null) {
            $context = $this->context;
        }

        return $context;
    }


    /**
     * Formatte la clé d'une variable
     *
     * @param string $key
     *
     * @return string
     */
    protected function formatKey(string $key) {
        return $key;
    }

    /**
     * Retourne la configuration de DrupSettings contextualisée par le contexte par défaut de la classe
     *
     * @return LanguageConfigOverride
     */
    protected function getConfigValues() {
        return $this->configValues;
    }

    /**
     * Retourne une configuration contextualisée
     *
     * @param string $context
     *
     * @return \Drupal\language\Config\LanguageConfigOverride
     */
    protected function getConfigValuesByContext(string $context) {
        return $this->languageManager->getLanguageConfigOverride($context, self::getConfigValuesName());
    }





    /**
     * @deprecated
     */
    public function setLanguage(string $languageId = null) {
        $this->applyContext($languageId);
    }
    /**
     * @deprecated
     */
    public function setNeutralLang() {
        $this->applyContext(self::$contextNeutral);
    }
    /**
     * @deprecated
     */
    public function set(string $key, $value, string $context = null) {
        $this->setValue($key, $value, $context);
    }
}
