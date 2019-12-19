<?php

namespace Drupal\drup\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Console\Command\Shared\TranslationTrait;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class DrupEntityAutocompleteMatcher
 *
 * @package Drupal\drup\Entity
 */
class DrupEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

    use TranslationTrait;

    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var EntityTypeBundleInfoInterface
     */
    protected $entityTypeBundleInfo;

    /**
     * @inheritDoc
     */
    public function __construct(SelectionPluginManagerInterface $selection_manager) {
        parent::__construct($selection_manager);

        $this->entityRepository = \Drupal::service('entity.repository');
        $this->entityTypeManager = \Drupal::entityTypeManager();
        $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    }

    /**
     * @inheritDoc
     */
    public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
        $matches = [];

        $options = $selection_settings + [
                'target_type' => $target_type,
                'handler' => $selection_handler,
            ];
        $handler = $this->selectionManager->getInstance($options);

        if (isset($string)) {
            // Get an array of matching entities.
            $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
            $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
            $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit);

            $storage = $this->entityTypeManager->getStorage($target_type);
            $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();

            // Loop through the entities and convert them into autocomplete output.
            foreach ($entity_labels as $values) {
                foreach ($values as $entity_id => $label) {
                    $custom_label = null;

                    if ($entity = $storage->load($entity_id)) {
                        $entity = $this->entityRepository->getTranslationFromContext($entity);

                        if (!ContentEntityBase::isAllowed($entity, $languageId)) {
                            continue;
                        }
                        $custom_label = $this->getEntityLabel($label, $entity);
                    }

                    $key = "$label ($entity_id)";
                    // Strip things like starting/trailing white spaces, line breaks and
                    // tags.
                    $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
                    // Names containing commas or quotes must be wrapped in quotes.
                    $key = Tags::encode($key);

                    $matches[] = [
                        'value' => $key,
                        'label' => $custom_label ?? $label,
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * @param string $label
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *
     * @return string
     */
    protected function getEntityLabel(string $label, EntityInterface $entity) {
        $label .= ' (' . $entity->id() . ')';

        // Add bundle label
        if (method_exists($entity, 'bundle')) {
            $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());
            if (isset($bundles[$entity->bundle()]['label'])) {
                $label .= ' - ' . $bundles[$entity->bundle()]['label'];
            }
        }

        // Add unpublished flag
        if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
            $label .= ' - ' . t('Unpublished');
        }

        return $label;
    }


    /**
     * @param string $target_type
     * @param string $selection_handler
     * @param array $selection_settings
     * @param string $string
     *
     * @return array
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
//    public function _getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
//        $matches = [];
//
//        $options = [
//            'target_type' => $target_type,
//            'handler' => $selection_handler,
//            'handler_settings' => $selection_settings,
//        ];
//
//        $handler = $this->selectionManager->getInstance($options);
//
//        if ($string !== null) {
//            $languageId = \Drupal::languageManager()->getCurrentLanguage()
//                ->getId();
//            // Get an array of matching entities.
//            $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
//            $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);
//
//            // Loop through the entities and convert them into autocomplete output.
//            foreach ($entity_labels as $values) {
//                foreach ($values as $entity_id => $label) {
//                    /** @var \Drupal\Core\Entity\ContentEntityBase $entity * */
//                    $entity = \Drupal::entityTypeManager()
//                        ->getStorage($target_type)->load($entity_id);
//                    $entity = ContentEntityBase::translate($entity, $languageId);
//
//                    $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
//                    $type = ucfirst(t($type));
//
//                    $status = '';
//                    if (method_exists($entity, 'isPublished')) {
//                        if (!ContentEntityBase::isAllowed($entity, $languageId)) {
//                            continue;
//                        }
//                        $status = $entity->isPublished() ? 'published' : 'unpublished';
//                    }
//
//                    $key = $label . ' (' . $entity_id . ')';
//                    // Strip things like starting/trailing white spaces, line breaks and tags.
//                    $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
//                    // Names containing commas or quotes must be wrapped in quotes.
//                    $key = Tags::encode($key);
//
//                    if (!empty($status)) {
//                        $label = $label . ' [' . $type . ', ' . t($status) . ']';
//                    }
//
//                    $matches[] = ['value' => $key, 'label' => $label];
//                }
//            }
//        }
//
//        return $matches;
//    }
}
