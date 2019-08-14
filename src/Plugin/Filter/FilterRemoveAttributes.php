<?php

namespace Drupal\drup\Plugin\Filter;

use Drupal\drup\Helper\DrupString;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to remove HTML attributes
 *
 * @Filter(
 *   id = "filter_remove_attributes",
 *   title = @Translation("Remove attributes"),
 *   description = @Translation("Remove HTML attributes"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FilterRemoveAttributes extends FilterBase {

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode) {
        $result = new FilterProcessResult($text);

        if (!empty($this->settings['list'])) {
            $list = explode(' ', $this->settings['list']);
            $replace = DrupString::removeAttributes($result->getProcessedText(), $list);

            if ($replace !== null) {
                $result->setProcessedText($replace);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form['list'] = [
            '#type' => 'textfield',
            '#title' => $this->t('List of attributes'),
            '#default_value' => !empty($this->settings['list']) ? $this->settings['list'] : '',
            '#description' => $this->t('List of attributes name to remove, separate by spaces.'),
        ];
        return $form;
    }

}