<?php

namespace Drupal\drup\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\drup\Helper\DrupString;
use Drupal\field\Entity\FieldConfig;

/**
 *
 * Class DrupField
 *
 * @package Drupal\drup\Entity
 */
class DrupField {

    /**
     * @var \Drupal\drup\Entity\ContentEntityBase
     */
    protected $entity;

    /**
     * DrupField constructor.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     */
    public function __construct(EntityInterface $entity) {
        $this->entity = $entity;
    }

    /**
     * @param string $field
     *
     * @return \Drupal\Core\Field\FieldItemList|bool
     */
    public function get($field) {
        if ($this->entity->hasField(self::format($field)) && ($data = $this->entity->get(self::format($field))) && !$data->isEmpty()) {
            return $data;
        }

        return false;
    }

    /**
     * @param string $field
     * @param null|string $key
     *
     * @return mixed|null
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getValue($field, $key = null) {
        if (($fieldEntity = $this->get($field)) && ($fistField = $fieldEntity->first()) && ($data = $fistField->getValue())) {
            if (is_string($key)) {
                if (isset($data[$key])) {
                    return $data[$key];
                }
            } else {
                return $data;
            }
        }

        return null;
    }

    /**
     * @param string $field
     * @param null|string $key
     *
     * @return array
     */
    public function getValues($field, $key = null) {
        $values = [];

        if ($fields = $this->get($field)) {
            foreach ($fields->getIterator() as $index => $fieldEntity) {
                if ($data = $fieldEntity->getValue()) {
                    if (is_string($key)) {
                        if (isset($data[$key])) {
                            $values[] = $data[$key];
                        }
                    } else {
                        $values[] = $data;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @param string $field
     * @param string $key
     *
     * @return mixed|null
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function getProcessedText($field, $key = 'value') {
        if (($fieldEntity = $this->get($field)) && ($fistField = $fieldEntity->first()) && ($data = $fistField->getValue())) {
            if (!empty($data) && isset($data[$key])) {
                return [
                    '#type' => 'processed_text',
                    '#text' => $data[$key],
                    '#format' => $data['format']
                ];
            }
        }

        return null;
    }

    /**
     * @param string $field
     *
     * @return \Drupal\Core\Entity\Entity[]
     */
    public function getReferencedEntities($field) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $fields */
        if ($fields = $this->get($field)) {
            return $fields->referencedEntities();
        }

        return [];
    }

    /**
     * @param $field
     * @param $type
     * @param mixed ...$parameters
     *
     * @return null|\Drupal\drup\Media\DrupMediaImage|\Drupal\drup\Media\DrupMediaDocument|\Drupal\drup\Media\DrupMediaVideoExternal
     */
    public function getDrupMedia($field, $type, ...$parameters) {
        $entities = $this->getReferencedEntities($field);
        $className = '\\Drupal\\drup\\Media\\DrupMedia' . DrupString::toCamelCase($type);

        if (class_exists($className)) {
            $parameters = array_merge([$entities], $parameters);
            $drupMedia = new $className(...$parameters);

            return !$drupMedia->isEmpty() ? $drupMedia : null;
        }

        return null;
    }

    /**
     * @param string $field
     * @param null $key
     * @param string $type
     * @param string $format
     *
     * @return array
     */
    public function formatDate($field, $key = null, $type = 'medium', $format = '') {
        $datesFormatted = [];

        if ($fieldsDate = $this->get($field)) {
            $dateTypes = ['date', 'start_date', 'end_date'];

            if ($key !== null && isset($dateTypes[$key])) {
                $dateTypes = [$key];
            }

            foreach ($fieldsDate->getIterator() as $index => $fieldDate) {
                foreach ($dateTypes as $dateType) {
                    if ($fieldDate->{$dateType} instanceof DrupalDateTime) {
                        $datesFormatted[$index][$dateType] = \Drupal::service('date.formatter')->format($fieldDate->{$dateType}->getTimestamp(), $type, $format);
                    }
                }
            }
        }

        return $datesFormatted;
    }

    /**
     * @param array|object $input
     * @param string $field
     * @param null $fieldKey
     * @param null $outputKey
     * @param bool $multiple
     */
    public function add(&$input, string $field, $fieldKey = null, $outputKey = null, $multiple = false) {
        if (empty($outputKey)) {
            $outputKey = $field;
        }

        $value = $this->{$multiple ? 'getValues' : 'getValue'}($field, $fieldKey);

        if (is_array($input)) {
            $input[$outputKey] = $value;

        } else {
            $input->{$outputKey} = $value;
        }
    }

    /**
     * @param $field
     *
     * @return \Drupal\field\Entity\FieldConfig
     */
    public function getConfig($field) {
        return FieldConfig::loadByName($this->entity->getEntityTypeId(), $this->entity->bundle(), self::format($field));
    }

    /**
     * @param $field
     *
     * @return mixed
     */
    public function getDisplayConfig($field) {
        $formDisplay = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($this->entity->getEntityTypeId() . '.' . $this->entity->bundle() . '.default');

        /** @var \Drupal\Core\Entity\EntityDisplayBase $formDisplay **/
        if ($formDisplay !== null) {
            return $formDisplay->getComponent(self::format($field));
        }

        return null;
    }

    /**
     * @param $field
     *
     * @return string
     */
    public static function format($field) {
        return in_array($field, ['title']) ? $field : 'field_' . $field;
    }
}
