<?php

namespace Drupal\drup_social_links;

use Drupal\Core\Url;
use Drupal\drup\DrupPageEntity;
use Drupal\language\Config\LanguageConfigOverride;

/**
 * Class DrupSocialLinks
 *
 * @package Drupal\drup_social_links
 */
class DrupSocialLinks {

    /**
     * Nom de la configuration
     *
     * @var string
     */
    protected static $configName = 'drup.social_links';

    /**
     * Retourne le nom de la configuration
     *
     * @return string
     */
    public static function getConfigName() {
        return self::$configName;
    }

    /**
     * Retourne la configuration de DrupSocialLinks
     *
     * @return bool|LanguageConfigOverride
     */
    public static function getConfig() {
        $languageManager = \Drupal::languageManager();
        $languageId = $languageManager->getCurrentLanguage()->getId();
        $config = $languageManager->getLanguageConfigOverride($languageId, self::getConfigName());

        if ($config instanceof LanguageConfigOverride) {
            return $config;
        }

        return false;
    }

    /**
     * Retourne les items enregistrés pour la lecture seulement (formatés)
     *
     * @return array|mixed|null
     */
    public static function getItems() {
        if (($config = self::getConfig()) && ($items = $config->get('items')) && !empty($items)) {
            self::formatItems($items);

            return $items;
        }

        return [];
    }

    /**
     * Formattage des éléments à retourner
     *
     * @param $items
     */
    protected static function formatItems(&$items) {
        if (!empty($items)) {
            foreach ($items as $i => $item) {
                $items[$i]['link'] = (bool) $item['link'];
                $items[$i]['share'] = (bool) $item['share'];

                if (!$items[$i]['link_url'] instanceof Url) {
                    $items[$i]['link_url'] = Url::fromUri($items[$i]['link_url']);
                }

                $options = explode(',', $item['options']);
                $items[$i]['options'] = [];
                if (!empty($options)) {
                    foreach ($options as $option) {
                        if (!empty($option)) {
                            [$key, $value] = explode('=', $option);
                            $items[$i]['options'][trim($key)] = trim($value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Retourne les liens vers les réseaux sociaux
     *
     * @return array
     */
    public static function getLinkItems() {
        $items = self::getItems();

        if (!empty($items)) {
            $items = array_filter($items, function ($item) {
                return $item['link'];
            });
        }

        return $items;
    }

    /**
     * Retourne les liens pour le partage sur les réseaux sociaux
     *
     * @param $currentEntity
     *
     * @return array
     */
    public static function getShareItems($currentEntity = null) {
        $items = self::getItems();

        if (!empty($items)) {
            $token = \Drupal::token();
            $drupPageEntity = $currentEntity === null ? DrupPageEntity::loadEntity() : $currentEntity;

            foreach ($items as $id => $item) {
                if (!$item['share']) {
                    unset($items[$id]);
                    continue;
                }

                $shareUrlTokens = $token->scan($item['share_url']);
                $replaceOptions = [];

                if ($drupPageEntity) {
                    $replaceOptions[$drupPageEntity->getEntityType()] = $drupPageEntity->getEntity();
                }

                foreach ($shareUrlTokens as $shareUrlTokenGroup => $shareUrlToken) {
                    $items[$id]['share_url'] = Url::fromUri($token->replace($item['share_url'], $replaceOptions));
                }
            }
        }

        return $items;
    }
}
