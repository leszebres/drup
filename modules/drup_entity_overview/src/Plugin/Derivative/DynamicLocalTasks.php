<?php

namespace Drupal\drup_entity_overview\Plugin\Derivative;


use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

    use StringTranslationTrait;

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Creates an DevelLocalTask object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity manager.
     * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
     *   The translation manager.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
        $this->entityTypeManager = $entity_type_manager;
        $this->stringTranslation = $string_translation;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, $base_plugin_id) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('string_translation')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDerivativeDefinitions($base_plugin_definition) {
        $this->derivatives = [];

        foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
            $this->derivatives["$entity_type_id.kikou.devel_tab"] = [
                'route_name' => "entity.$entity_type_id.entity_overview",
                'title' => $this->t('Find usage'),
                'base_route' => "entity.$entity_type_id.canonical",
                'weight' => 100,
            ];
        }

        foreach ($this->derivatives as &$entry) {
            $entry += $base_plugin_definition;
        }

        return $this->derivatives;
    }

}
