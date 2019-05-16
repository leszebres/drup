<?php

namespace Drupal\drup\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'drup_iconpicker_default' formatter.
 *
 * @FieldFormatter(
 *   id = "drup_iconpicker_default",
 *   label = @Translation("Default"),
 *   field_types = {"drup_iconpicker"}
 * )
 */
class IconPickerDefaultFormatter extends FormatterBase {

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        return [];
    }

}
