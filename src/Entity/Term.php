<?php

namespace Drupal\drup\Entity;

/**
 * Class Term
 *
 * @package Drupal\drup\Entity
 */
class Term extends \Drupal\taxonomy\Entity\Term {

    /**
     * @inheritdoc
     */
    public static function load($id) {
        if ($entity = parent::load($id)) {
            return ContentEntityBase::translate($entity);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public static function loadMultiple(array $ids = null) {
        if ($entities = parent::loadMultiple($ids)) {
            foreach ($entities as $index => &$entity) {
                $entity = ContentEntityBase::translate($entity);
            }

            return $entities;
        }

        return [];
    }

    /**
     * @param null $languageId
     *
     * @return bool
     */
    public function isTranslated($languageId = null) {
        return ContentEntityBase::isTranslated($this, $languageId);
    }

    /**
     * @param null $languageId
     *
     * @return \Drupal\Core\Entity\ContentEntityBase
     */
    public function translate($languageId = null) {
        return ContentEntityBase::translate($this, $languageId);
    }

    /**
     * @return \Drupal\drup\Entity\DrupField
     */
    public function drupField() {
        return new DrupField($this);
    }


    /**
     * @param string $vid
     * @param int $parent
     * @param int $maxDepth
     *
     * @return array
     */
    public static function loadTree($vid, $parent = 0, $maxDepth = 1) {
        $tree = [];
        $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree($vid, $parent, $maxDepth, true);

        if (!empty($terms)) {
            foreach ($terms as $tid => &$term) {
                /** @var \Drupal\drup\Entity\Term $term */
                if (($term = self::load($term->id())) && $term->isTranslated($languageId)) {
                    $tree[] = (object) [
                        'term' => $term,
                        'children' => self::loadTree($vid, $term->id(), $maxDepth)
                    ];
                }
            }
        }

        return $tree;
    }
}
