<?php

namespace Drupal\drup_report\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drup_report\DrupReport;

/**
 * Class DrupReportForm.
 */
class DrupReportForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [DrupReport::getConfigName()];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'drup_report_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        DrupReport::saveLogTypes();

        $config = $this->config(DrupReport::getConfigName());
        $user = \Drupal::currentUser();
        $userId = $user->id();

        // USER
        $form['user_id'] = [
            '#type' => 'hidden',
            '#value' => $userId,
        ];
        $form['user_name'] = [
            '#type' => 'textfield',
            '#title' => ucfirst($this->t('user')),
            '#default_value' => $user->getDisplayName(),
            '#attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ];

        // CONFIGURATION
        $values = DrupReport::getConfigValuesByUser($userId);
        $form['enable'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable feature'),
            '#default_value' => !empty($values),
        ];

        $form['config'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Report configuration'),
            '#states' => [
                'visible' => [
                    ':input[name="enable"]' => ['checked' => true],
                ],
            ],
        ];
        $form['config']['types'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Log types to display'),
            '#description' => $this->t('If none selected, all will be available.'),
            '#options' => $config->get('types'),
            '#default_value' => $values['types'] ?? []
        ];

        $form['config']['periodicity'] = [
            '#type' => 'select',
            '#title' => $this->t('Receive notifications'),
            '#options' => [
                'minute' => $this->t('Minute'),
                'hour' => $this->t('Hourly'),
                'day' => $this->t('Daily'),
                'week' => $this->t('Weekly'),
                'month' => $this->t('Monthly'),
            ],
            '#default_value' => $values['periodicity'] ?? 'day',
        ];

        $form['config']['start_date'] = [
            '#type' => 'datetime',
            '#title' => $this->t('Starting date'),
            '#default_value' => isset($values['start_date']) ? DrupalDateTime::createFromTimestamp($values['start_date']) : new DrupalDateTime()
        ];

        if (isset($values['date_last_send'])) {
            $form['config']['date_last_send_info'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Last report sent on'),
                '#default_value' => DrupalDateTime::createFromTimestamp($values['date_last_send'])->format('d/m/Y H:i:s'),
                '#attributes' => [
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                ],
            ];
        }

        $form['config']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email override'),
            '#description' => $this->t('Instead, logs will be sent by default to "@key"', ['@key' => $user->getEmail()]),
            '#default_value' => $values['email'] ?? null,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config(DrupReport::getConfigName());
        $userId = $form_state->getValue('user_id');

        $formValues = [];
        if ((bool) $form_state->getValue('enable') === true) {
            $formValues = [
                'user'           => $userId,
                'types'          => array_filter($form_state->getValue('types')),
                'periodicity'    => $form_state->getValue('periodicity'),
                'start_date'     => $form_state->getValue('start_date')->format('U'),
                'date_last_send' => null,
                'email'          => $form_state->getValue('email')
            ];
        }


        $data = $config->get('data');
        $data[$userId] = $formValues;
        $config->set('data', $data)->save();

        parent::submitForm($form, $form_state);
    }
}
