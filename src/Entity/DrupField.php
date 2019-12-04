<?php

namespace Drupal\drup\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityBase;
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
     * @var EntityBase
     */
    protected $entity;

    /**
     * DrupField constructor.
     *
     * @param EntityBase $entity
     */
    public function __construct(EntityBase $entity) {
        $this->entity = $entity;
    }

    /**
     * @return EntityBase
     */
    public function entity() {
        return $this->entity;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function hasField(string $field) {
        return $this->entity()->hasField(self::format($field));
    }

    /**
     * @param string $field
     *
     * @return \Drupal\Core\Field\FieldItemList|bool
     */
    public function get(string $field) {
        if ($this->hasField($field) && ($data = $this->entity()->get(self::format($field))) && !$data->isEmpty()) {
            return $data;
        }

        return false;
    }

    /**
     * @param string $field
     * @param null|string $key
     *
     * @return string|array|null
     */
    public function getValue(string $field, string $key = null) {
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
     * @param string|null $key
     *
     * @return array
     */
    public function getValues(string $field, string $key = null) {
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
     * @return array|null
     */
    public function getProcessedText(string $field, string $key = 'value') {
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
     * @return array|\Drupal\Core\Entity\EntityInterface[]
     */
    public function getReferencedEntities(string $field) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $fields */
        if ($fields = $this->get($field)) {
            return $fields->referencedEntities();
        }

        return [];
    }

    /**
     * @param string $field
     * @param string $type
     * @param mixed ...$parameters
     *
     * @return \Drupal\drup\Media\DrupMedia|null
     */
    public function getDrupMedia(string $field, string $type, ...$parameters) {
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
     * @param string|null $key
     * @param string $type
     * @param string $format
     *
     * @return array
     */
    public function formatDate(string $field, string $key = null, string $type = 'medium', string $format = '') {
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
     * @param $input
     * @param string $field
     * @param string|null $fieldKey
     * @param string|null $outputKey
     * @param bool $multiple
     */
    public function add(&$input, string $field, string $fieldKey = null, string $outputKey = null, bool $multiple = false) {
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
     * @param string $field
     *
     * @return \Drupal\field\Entity\FieldConfig
     */
    public function getConfig(string $field) {
        return FieldConfig::loadByName($this->entity()->getEntityTypeId(), $this->entity()->bundle(), self::format($field));
    }

    /**
     * @param string $field
     *
     * @return array|null
     */
    public function getDisplayConfig(string $field) {
        $formDisplay = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($this->entity()->getEntityTypeId() . '.' . $this->entity()->bundle() . '.default');

        /** @var \Drupal\Core\Entity\EntityDisplayBase $formDisplay **/
        if ($formDisplay !== null) {
            return $formDisplay->getComponent(self::format($field));
        }

        return null;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public static function format(string $field) {
        return in_array($field, ['title']) ? $field : 'field_' . $field;
    }
}
