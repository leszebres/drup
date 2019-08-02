<?php

namespace Drupal\drup\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Url;

/**
 * Créer un élement de type "link" (lien interne avec autocomplétion / lien externe)
 *
 * @FormElement("drup_link")
 */
class DrupLink extends FormElement {

    /**
     * {@inheritdoc}
     */
    public function getInfo() {
        $class = \get_class($this);

        return [
            '#input' => true,
            '#size' => 60,
            '#autocomplete_route_name' => 'system.entity_autocomplete',
            '#target_type' => 'node',
            '#selection_handler' => 'default',
            '#selection_settings' => [],
            '#tags' => false,
            '#autocreate' => null,
            '#process_default_value' => false,
            '#process' => [
                [$class, 'processDrupLink'],
                [$class, 'processAutocomplete'],
                [$class, 'processAjaxForm'],
                [$class, 'processPattern'],
                [$class, 'processGroup']
            ],
            '#pre_render' => [
                [$class, 'preRenderDrupLink'],
                [$class, 'preRenderGroup']
            ],
            '#theme' => 'input__textfield',
            '#theme_wrappers' => ['form_element']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
        return EntityAutocomplete::valueCallback($element, $input, $form_state);
    }

    /**
     * @see EntityAutocomplete::processEntityAutocomplete
     */
    public static function processDrupLink(array &$element, FormStateInterface $form_state, array &$complete_form) {
        return EntityAutocomplete::processEntityAutocomplete($element, $form_state, $complete_form);
    }

    /**
     * {@inheritdoc}
     */
    public static function preRenderDrupLink($element) {
        return Textfield::preRenderTextfield($element);
    }

    /**
     * Return url according to internal/external input
     *
     * @param string $input
     * @param string $target_type
     *
     * @return null|\Drupal\Core\Url
     */
    public static function getUrl(string $input, string $target_type = null, $options = []) {
        $url = null;

        // Internal Uri
        if (!empty($target_type) && ($targetId = self::getTargetId($input)) !== false) {
            $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($targetId);

            if ($entity !== null) {
                $url = $entity->toUrl('canonical', $options);
            }
        }
        // External Uri
        elseif (!empty($input)) {
            $url = Url::fromUri($input, $options);
            $url->setOption('external', true);
        }

        return $url;
    }

    /**
     * Extract target id from input string
     *
     * @param string $input
     *
     * @return bool|string
     */
    public static function getTargetId(string $input) {
        if (!empty($input)) {
            $matches = [];
            preg_match('/\s\((\d)\)/', $input, $matches);

            if (!empty($matches)) {
                return (string) $matches[1];
            }
        }

        return false;
    }

    /**
     * Check if the input is internal
     *
     * @param string $input
     *
     * @return bool
     */
    public static function isInternal(string $input): bool {
        return self::getTargetId($input) !== false;
    }

    /**
     * Check if the input is external
     *
     * @param string $input
     *
     * @return bool
     */
    public static function isExternal(string $input): bool {
        return !self::isInternal($input);
    }
}
