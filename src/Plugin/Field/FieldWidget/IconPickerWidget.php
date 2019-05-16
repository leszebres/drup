<?php

namespace Drupal\drup\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'drup_iconpicker' field widget.
 *
 * @FieldWidget(
 *   id = "drup_iconpicker",
 *   label = @Translation("IconPicker"),
 *   field_types = {"drup_iconpicker"},
 * )
 */
class IconPickerWidget extends WidgetBase {

    /**
     * Classe CSS utilisée pour le prefix
     *
     * @var string
     */
    protected $prefix = 'icon';

    /**
     * Type de fonticon permettant d'ajouter une classe "{prefix}-type--{type}"
     *
     * @var string
     */
    protected $type = 'map';

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $element['icon'] = [
            '#type'  => 'textfield',
            '#title' => isset($element['#title']) ? $element['#title'] : $this->t('Icon'),
            '#default_value' => isset($items[$delta]->icon) ? $this->prefix . ' ' . $this->prefix . '-type--' . $this->type . ' ' . $this->prefix . '--' . $items[$delta]->icon : null
        ];

        $element['#attached']['library'][] = 'drup/drup_iconpicker';
        $element['#attached']['drupalSettings']['drup_iconpicker'] = [
            'prefix' => $this->prefix,
            'type' => $this->type,
            'input' => 'edit-' . Html::getId($items->getName()) . '-' . $delta . '-icon'
        ];

        $pathToTheme = \Drupal::service('theme_handler')->getTheme('drup_theme')->getPath();
        $element['#attached']['drupalSettings']['drup_iconpicker']['font_path'] = $pathToTheme . '/fonts/map/icons.svg';

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
        return isset($violation->arrayPropertyPath[0]) ? $element[$violation->arrayPropertyPath[0]] : $element;
    }

    /**
     * {@inheritdoc}
     */
    public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
        foreach ($values as $delta => $value) {
            if ($value['icon'] === '') {
                $values[$delta]['icon'] = null;
            } else {
                $values[$delta]['icon'] = str_replace([
                    $this->prefix . ' ',
                    $this->prefix . '-type--' . $this->type . ' ',
                    $this->prefix . '--',
                ], '', $value['icon']);
            }
        }
        return $values;
    }
}
