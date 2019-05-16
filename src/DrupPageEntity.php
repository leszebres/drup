<?php

namespace Drupal\drup;

use Drupal\drup\Helper\DrupRequest;

/**
 * Class DrupPageEntity
 *
 * @package Drupal\drup
 */
class DrupPageEntity {

    /**
     * @var array
     */
    protected $args;

    /**
     * @var \Drupal\Core\Entity\Entity
     */
    protected $entity;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $bundle;

    /**
     * DrupPageEntity constructor.
     */
    public function __construct() {
        $this->args = in_array(\Drupal::routeMatch()->getRouteName(), [
            'views.ajax',
            'entity.node.preview',
        ]) ? DrupRequest::getPreviousRouteParameters() : DrupRequest::getArgs();

        $this->setData($this->loadData());
    }

    /**
     * @param bool $loadEntity
     *
     * @return mixed
     */
    public function loadData() {
        $data = (object) [
            'entity' => null,
            'type' => null,
            'bundle' => null,
            'id' => null
        ];

        if (!empty($this->args)) {
            $entityType = current(array_keys($this->args));

            if (!empty($entityType) && !in_array($entityType, ['entity', 'uid'])) {
                $entity = $this->args[$entityType];

                if (is_object($entity)) {
                    $data->entity = $entity;
                    $data->id = method_exists($data->entity, 'id') ? (int)$data->entity->id() : null;

                } else {
                    if (\Drupal::entityTypeManager()->getDefinition($entityType, false) === null) {
                        return $data;
                    }
                    $data->entity = \Drupal::entityTypeManager()->getStorage($entityType)->load($entity);
                    $data->id = (int) $entity;
                }

                $data->type = $entityType;
                $data->bundle = method_exists($data->entity, 'bundle') ? $data->entity->bundle() : null;
            }
        }

        return $data;
    }

    /**
     * @param \StdClass $data
     */
    protected function setData(\StdClass $data) {
        $this->entity = $data->entity;
        $this->type = $data->type;
        $this->id = $data->id;
        $this->bundle = $data->bundle;
    }

    /**
     * @return string
     */
    public function id() {
        return $this->id;
    }

    /**
     * @return \Drupal\Core\Entity\Entity
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getEntityType() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getBundle() {
        return $this->bundle;
    }

    /**
     * @return DrupPageEntity
     */
    public static function loadEntity() {
        return new static;
    }
}
