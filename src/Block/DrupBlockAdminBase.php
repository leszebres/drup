<?php

namespace Drupal\drup\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\drup\Helper\DrupRequest;

/**
 * Class DrupBlockAdminBase
 *
 * @package Drupal\drup\Block
 */
abstract class DrupBlockAdminBase extends BlockBase {

    /**
     * @var
     */
    public $languageId;

    /**
     * Block id
     * @var
     */
    public $blockId;

    /**
     * @var string
     */
    public $urlContextKey;

    /**
     * Context for block values (entityType.entityId|front) from url
     * @var
     */
    public $urlContextValue;

    /**
     * Block values key storage (blockId.Language.$context)
     * @var
     */
    public $configKey;

    /**
     * Block storage config
     * @var \Drupal\Core\Config\Config
     */
    public $config;

    /**
     * Bloc storage config values
     * @var
     */
    public $configValues;

    /**
     * Form values after submit
     * @var
     */
    public $formValues;

    /**
     * Ajax items form container
     * @var
     */
    public $ajaxContainer = 'items';

    /**
     * Max count items for ajax form
     * @var
     */
    public $ajaxMaxRows = -1;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        $this->languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $this->urlContextKey = 'drup-blocks-context';

        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        if (isset($this->configuration['id'])) {
            $this->blockId = $this->configuration['id'];

            $view = DrupRequest::isAdminRoute() ? 'admin' : 'front';
            $this->urlContextValue = $this->getUrlContextValue($view);

            $this->config = \Drupal::service('config.factory')->getEditable('drup_blocks.admin_values');

            $this->configKey = $this->blockId . '.' . $this->languageId . '.' . $this->urlContextValue;
            $this->configValues = $this->config->get($this->configKey);
        }

        return parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        parent::blockForm($form, $form_state);

        if (empty($this->urlContextValue)) {
            \Drupal::messenger()->addMessage($this->t('Missing context'), 'error');
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        if (isset($this->configValues[$this->ajaxContainer])) {
            unset($this->configValues[$this->ajaxContainer]['actions']);
        }
        
        $this->config->set($this->configKey, $this->configValues);
        $this->config->save();
    }

    /**
     * @return array|void
     */
    public function build() {}

    /**
     * @param array $parameters
     *
     * @return array
     */
    public function mergeBuildParameters($parameters = []) {
        $adminUrl = null;

        if (\Drupal::currentUser()->hasPermission('administer blocks')) {
            $adminUrl = Url::fromRoute('entity.block.edit_form', ['block' => $this->blockId], [
                'query' => [
                    'destination' => \Drupal::destination()->get(),
                    $this->urlContextKey => $this->urlContextValue
                ]
            ]);
        }
        $parameters = array_merge_recursive($parameters, [
            '#admin_url' => $adminUrl
        ]);
        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheContexts() {
        return Cache::mergeContexts(parent::getCacheContexts(), DrupBlock::getDefaultCacheContexts());
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags() {
        return Cache::mergeTags(parent::getCacheTags(), DrupBlock::getDefaultCacheTags());
    }

    /**
     * @param $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function buildAjaxContainer(&$form, FormStateInterface $form_state) {
        if (!$form_state->has('ajax_count_items')) {
            $form_state->set('ajax_count_items', is_array($this->configValues[$this->ajaxContainer]) ? count($this->configValues[$this->ajaxContainer]) : 0);
        }
        $countItems = &$form_state->get('ajax_count_items');

        $form['#tree'] = true;
        $form[$this->ajaxContainer] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Items'),
            '#prefix' => '<div id="ajax-items-fieldset-wrapper">',
            '#suffix' => '</div>',
        ];

        for ($i = 0; $i < $countItems; $i++) {
            //$itemIndex = 'item_' . $i;
            $itemIndex = $i;
            $values = $this->configValues[$this->ajaxContainer][$itemIndex] ?? [];

            $form[$this->ajaxContainer][$itemIndex] = [
                '#type' => 'details',
                '#collapsible' => true,
                '#open' => empty($values),
                //'#collapsed' => !empty($values),
                //'#collapsed' => false,
                '#title' => $this->t('Item') . ' #' . ($i + 1)
            ];
            $this->setAjaxRow($form[$this->ajaxContainer][$itemIndex], $values);
        }

        $form[$this->ajaxContainer]['actions'] = [
            '#type' => 'actions',
        ];
        $form[$this->ajaxContainer]['actions']['add_item'] = [
            '#type' => 'submit',
            '#value' => t('Add content'),
            '#submit' => [[$this, 'ajaxAddRow']],
            '#ajax' => [
                'callback' => [$this, 'ajaxCallback'],
                'wrapper' => 'ajax-items-fieldset-wrapper',
            ],
        ];
        if ($countItems > 1) {
            $form[$this->ajaxContainer]['actions']['remove_item'] = [
                '#type' => 'submit',
                '#value' => t('Remove this item'),
                '#submit' => [[$this, 'ajaxRemoveRow']],
                '#ajax' => [
                    'callback' => [$this, 'ajaxCallback'],
                    'wrapper' => 'ajax-items-fieldset-wrapper',
                ]
            ];
        }
    }

    /**
     * Set default fields for each row
     * Copy this function in Block extended class
     * @param $rowContainer
     * @param $rowValues
     */
    public function setAjaxRow(&$rowContainer, $rowValues) {
        /*
        $rowContainer['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#default_value' => !empty($rowValues['title']) ? $rowValues['title'] : null
        ];*/
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function ajaxAddRow(array &$form, FormStateInterface $form_state) {
        $countItems = &$form_state->get('ajax_count_items');

        if ($this->ajaxMaxRows !== -1 && $countItems >= $this->ajaxMaxRows) {
            \Drupal::messenger()->addWarning($this->t('You can manage only @count items.', ['@count' => $this->ajaxMaxRows]));
        } else {
            $form_state->set('ajax_count_items', $countItems + 1);
        }

        $form_state->setRebuild();
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function ajaxRemoveRow(array &$form, FormStateInterface $form_state) {
        $countItems = &$form_state->get('ajax_count_items');

        if ($countItems > 1) {
            $form_state->set('ajax_count_items', $countItems - 1);
        }

        $form_state->setRebuild();
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * @return mixed
     */
    public function ajaxCallback(array &$form, FormStateInterface $form_state) {
        return $form['settings'][$this->ajaxContainer];
    }

    /**
     * @param string $view
     *
     * @return string|null
     */
    public function getUrlContextValue($view = 'admin') {
        if ($view === 'admin') {
            return \Drupal::request()->query->get($this->urlContextKey);
        }
        if (DrupRequest::isFront()) {
            return 'front';
        }
        /** @var \Drupal\drup\DrupPageEntity $drupPageEntity */
        $drupPageEntity = \Drupal::service('drup_page_entity');
        if ($drupPageEntity->id()) {
            return $drupPageEntity->getEntityType() . '/' . $drupPageEntity->id();
        }

        return null;
    }
}
