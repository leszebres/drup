<?php

namespace Drupal\drup\Entity;

/**
 * Class ContentEntityBase
 *
 * @package Drupal\drup\Entity
 */
class ContentEntityBase extends \Drupal\Core\Entity\ContentEntityBase {

    /**
     * @param \Drupal\Core\Entity\ContentEntityBase $entity
     * @param null $languageId
     *
     * @return bool
     */
    public static function isTranslated(\Drupal\Core\Entity\ContentEntityBase $entity, $languageId = null) {
        $isAllowed = true;

        if (method_exists($entity, 'getTranslationLanguages')) {
            if ($languageId === null) {
                $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
            }

            $translations = $entity->getTranslationLanguages();

            if (!isset($translations['und']) && !$entity->hasTranslation($languageId)) {
                $isAllowed = false;
            }
        }

        return $isAllowed;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    public static function isAllowed($entity, $languageId = null) {
        return self::isTranslated($entity, $languageId);
    }

    /**
     * @param \Drupal\Core\Entity\ContentEntityBase $entity
     * @param null $languageId
     *
     * @return \Drupal\Core\Entity\ContentEntityBase
     */
    public static function translate(\Drupal\Core\Entity\ContentEntityBase $entity, $languageId = null) {
        if ($languageId === null) {
            $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
        }

        if ($entity->hasTranslation($languageId)) {
            return $entity->getTranslation($languageId);
        }

        return $entity;
    }
}
