<?php

namespace Drupal\drup_entity_overview;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class EntityOverviewUsageManager
 *
 * @package Drupal\drup_entity_overview
 */
class EntityOverviewUsageManager {

    /**
     * @var array
     */
    private $fieldsInfo;

    /**
     * @var string
     */
    protected static $configCid = 'drup_entity_overview:usage_fields';

    /**
     * @var \Drupal\Core\Language\LanguageManagerInterface
     */
    protected $languageManager;

    /**
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected $cacheBackend;

    /**
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * EntityOverviewUsageManager constructor.
     *
     * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
     * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
     * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(LanguageManagerInterface $languageManager, CacheBackendInterface $cacheBackend, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
        $this->languageManager = $languageManager;
        $this->cacheBackend = $cacheBackend;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;

        $this->fieldsInfo = $this->getConfig();
    }

    /**
     * @return array
     */
    public function getFields(): array {
        return $this->fieldsInfo;
    }

    /**
     * @param string $type
     * @param string|null $bundle
     *
     * @return array
     */
    public function getFieldsByEntityInfo(string $type, string $bundle): array {
        if (is_string($bundle)) {
            return $this->getFields()[$type . '.' . $bundle] ?? [];
        }
    }

    /**
     * @param string $entityType
     * @param string $entityBundle
     * @param string $entityId
     *
     * @return array
     */
    public function getReferencingEntities(string $entityType, $entityBundle, string $entityId): array {
        $entities = [];

        if ($fields = $this->getFieldsByEntityInfo($entityType, $entityBundle)) {
            foreach ($fields as $fieldInfo) {
                if (!isset($entities[$fieldInfo['entity_type']])) {
                    $entities[$fieldInfo['entity_type']] = [];
                }
                $query = $this->entityTypeManager->getStorage($fieldInfo['entity_type'])->getQuery()
                    ->condition($fieldInfo['field_name'], $entityId);

                $entitiesId = $query->execute();
                if (!empty($entitiesId) && ($entityTypeInterface = $this->entityTypeManager->getDefinition($fieldInfo['entity_type']))) {
                    /** @var ContentEntityInterface $storageHandler */
                    $storageHandler = $entityTypeInterface->getClass();
                    $entities[$fieldInfo['entity_type']] += $storageHandler::loadMultiple($entitiesId);
                }
            }
        }

        $entities = array_filter($entities);

        return $entities;
    }

    /**
     * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
     *
     * @return \Drupal\Core\Entity\ContentEntityInterface|null
     */
    public static function getParagraphParentEntity(Paragraph $paragraph) {
        if (($parent = $paragraph->getParentEntity()) && $parent->hasField($paragraph->get('parent_field_name')->value)) {
            $parentField = $paragraph->get('parent_field_name')->value;
            $field = $parent->get($parentField);

            foreach ($field as $key => $value) {
                if ($value->entity->id() === $paragraph->id()) {
                    return $parent;
                }
            }
        }
        return null;
    }

    /**
     * @return array
     */
    protected function getConfig(): array {
        if ($cache = $this->cacheBackend->get(self::$configCid)) {
            return $cache->data;
        }

        $data = $this->buildConfig();
        $this->cacheBackend->set(self::$configCid, $data);

        return $data;
    }

    /**
     * @return array
     */
    protected function buildConfig(): array {
        // Fields with Entity Reference Type, grouped by entities type
        $data = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
        $fields = [];

        foreach ($data as $entityType => $entityFields) {
            // Only localy created fields
            $entityReferenceFields = \array_filter($entityFields, static function ($value, $key) {
                return strpos($key, 'field_') === 0;
            }, \ARRAY_FILTER_USE_BOTH);

            if (!empty($entityReferenceFields)) {
                foreach ($entityReferenceFields as $fieldName => $entityReferenceField) {
                    foreach ($entityReferenceField['bundles'] as $entityBundle) {
                        $fieldConfig = FieldConfig::loadByName($entityType, $entityBundle, $fieldName);

                        // Get the field configured entities type + bundles
                        $targetType = $fieldConfig->getSetting('target_type');
                        $targetBundles = $fieldConfig->getSetting('handler_settings')['target_bundles'];
                        if (empty($targetBundles)) {
                            $targetBundles = [$targetType => $targetType];
                        }

                        $field = [
                            'entity_type' => $entityType,
                            'entity_bundle' => $entityBundle,
                            'field_name' => $fieldName,
                            'field_label' => $fieldConfig->getLabel(),
                            'field_entity_target_info' => [
                                'entity_type' => $targetType,
                                'bundles' => $targetBundles
                            ]
                        ];

                        // Organize data by referenced entities
                        foreach ($targetBundles as $index => $targetBundle) {
                            $fields[$targetType . '.' . $targetBundle][$entityType . '__' . $field['entity_bundle'] . '__' . $fieldName] = $field;
                        }
                    }
                }
            }
        }

        ksort($fields);

        return $fields;
    }
}
