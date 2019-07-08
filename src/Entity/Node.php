<?php

namespace Drupal\drup\Entity;

/**
 * Class Node
 *
 * @package Drupal\drup\Entity
 */
class Node extends \Drupal\node\Entity\Node {

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
     * @return string
     */
    public function getName() {
        return $this->getTitle();
    }

    /**
     * @return array
     */
    public function getSiblings() {
        $entities = [
            'previous' => (object) [
                'operator' => '>',
                'sort' => 'ASC'
            ],
            'next' => (object) [
                'operator' => '<',
                'sort' => 'DESC'
            ]
        ];
        /** @var \Drupal\drup\DrupPageEntity $drupPageEntity */
        $drupPageEntity = \Drupal::service('drup_page_entity');

        if ($drupPageEntity->getEntity() !== null) {
            foreach ($entities as $type => $filters) {
                $query = \Drupal::entityQuery($drupPageEntity->getEntityType());
                $query->condition('status', 1);
                $query->condition('type', $drupPageEntity->getBundle());
                $query->condition('langcode', \Drupal::languageManager()->getCurrentLanguage()->getId());
                $query->condition('created', $drupPageEntity->getEntity()->getCreatedTime(), $filters->operator);
                $query->sort('created', $filters->sort);
                $query->range(0, 1);

                $result = $query->execute();
                $entities[$type] = !empty($result) ? self::load(current($result)) : null;
            }
        }

        return $entities;
    }
}
