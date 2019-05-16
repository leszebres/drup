<?php

namespace Drupal\drup_router\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;


/**
 * Class RouterForm.
 */
class DrupRouterForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'drup.routes',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'drup_router_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('drup.routes');
        $values = $config->get('routes');
        $languages = \Drupal::languageManager()->getLanguages();

        $tableHeader = [
            'targetType' => '',
            'routeName' => $this->t('Route'),
        ];
        foreach ($languages as $languageCode => $language) {
            $tableHeader[$languageCode] = $language->getName();
        }

        $form['routes'] = [
            '#type' => 'table',
            '#header' => $tableHeader,
            '#empty' => $this->t('No routes found'),
        ];

        if (!empty($values)) {
            foreach ($values as $routeName => $routeValues) {
                if (!empty($routeValues['routeName'])) {
                    $form['routes'][] = $this->setRow($routeValues, $languages);
                }
            }
        }
        $form['routes'][] = $this->setRow([
            'isNew' => true,
            'targetType' => 'node',
        ], $languages);
        $form['routes'][] = $this->setRow([
            'isNew' => true,
            'targetType' => 'taxonomy_term',
        ], $languages);

        return parent::buildForm($form, $form_state);
    }

    /**
     * @param $rowValues
     * @param $languages
     *
     * @return array
     */
    public function setRow($rowValues, $languages) {
        $row = [];
        $isNewRow = (isset($rowValues['isNew']) && $rowValues['isNew']);

        if (empty($rowValues['targetType'])) {
            $rowValues['targetType'] = 'node';
        }
        $targetTypeLabel = ($rowValues['targetType'] === 'taxonomy_term') ? 'term' : $rowValues['targetType'];

        $row['targetType'] = [
            '#type' => 'hidden',
            '#default_value' => $rowValues['targetType'],
        ];
        $row['routeName'] = [
            '#type' => 'textfield',
            '#title' => !$isNewRow ? $this->t('Route name') : $this->t('New @type route name', ['@type' => $targetTypeLabel]),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#placeholder' => !$isNewRow ? $this->t('Route name') : '',
            '#default_value' => !$isNewRow ? $rowValues['routeName'] : null,
        ];
        foreach ($languages as $languageCode => $language) {
            $languageName = $language->getName();

            $value = null;
            if (isset($rowValues[$languageCode])) {
                $value = $rowValues['targetType'] === 'taxonomy_term' ? Term::load($rowValues[$languageCode]) : Node::load($rowValues[$languageCode]);
            }

            $row[$languageCode] = [
                '#type' => 'entity_autocomplete',
                '#target_type' => $rowValues['targetType'],
                '#title' => !$isNewRow ? $this->t($languageCode . ' ' . $targetTypeLabel) : $this->t($languageName . ' ' . $targetTypeLabel),
                '#title_display' => !$isNewRow ? 'invisible' : 'before',
                '#placeholder' => !$isNewRow ? $this->t($languageCode . ' ' . $targetTypeLabel) : '',
                '#default_value' => $value,
            ];
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('drup.routes')
            ->set('routes', $form_state->getValue('routes'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
