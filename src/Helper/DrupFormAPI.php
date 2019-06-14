<?php

namespace Drupal\drup\Helper;

use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Class DrupFormAPI
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupFormAPI {

    /**
     * Transforme un champ en lien proposant autant des liens internes qu'externes
     *
     * @param $field
     */
    public static function itemInternalAndExternalLink(&$field) {
        // Force le type
        $field['#type'] = 'entity_autocomplete';

        // supprime la validation de l'entité
        $field['#process_default_value'] = false;

        // LinkWidget = champ de contenu de type "Link"
        $field['#element_validate'] = [[LinkWidget::class, 'validateUriElement']];

        // Valeur à afficher à l'utilisateur
        if (!empty($field['#default_value'])) {
            // Lien externe : default_value
            if (\Drupal\Component\Utility\UrlHelper::isExternal($field['#default_value'])) {
                $field['#value'] = $field['#default_value'];
            }
            // Lien interne pointant vers une entité : un simule [Titre (ID)]
            elseif (($url = Url::fromUri($field['#default_value'])) && ($entity = DrupUrl::loadEntity($url))) {
                $field['#value'] = $entity->getName() . ' (' . $entity->id() . ')';
            }
        }
    }
}
