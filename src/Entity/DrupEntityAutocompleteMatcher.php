<?php

namespace Drupal\drup\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

/**
 * Class DrupEntityAutocompleteMatcher
 *
 * @package Drupal\drup\Entity
 */
class DrupEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

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
    public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
        $matches = [];

        $options = [
            'target_type'      => $target_type,
            'handler'          => $selection_handler,
            'handler_settings' => $selection_settings,
        ];

        $handler = $this->selectionManager->getInstance($options);

        if ($string !== null) {
            $languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
            // Get an array of matching entities.
            $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
            $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

            // Loop through the entities and convert them into autocomplete output.
            foreach ($entity_labels as $values) {
                foreach ($values as $entity_id => $label) {
                    /** @var \Drupal\Core\Entity\ContentEntityBase $entity **/
                    $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
                    $entity = ContentEntityBase::translate($entity, $languageId);

                    $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
                    $type = ucfirst(t($type));

                    $status = '';
                    if (method_exists($entity, 'isPublished')) {
                        if (!ContentEntityBase::isAllowed($entity, $languageId)) {
                            continue;
                        }
                        $status = $entity->isPublished() ? 'published' : 'unpublished';
                    }

                    $key = $label . ' (' . $entity_id . ')';
                    // Strip things like starting/trailing white spaces, line breaks and tags.
                    $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
                    // Names containing commas or quotes must be wrapped in quotes.
                    $key = Tags::encode($key);

                    if (!empty($status)) {
                        $label = $label . ' [' . $type . ', ' . t($status) . ']';
                    }

                    $matches[] = ['value' => $key, 'label' => $label];
                }
            }
        }

        return $matches;
    }
}
