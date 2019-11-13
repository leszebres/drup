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
     * Langcode
     *
     * @var string
     */
    protected $languageId;

    /**
     * Block id
     *
     * @var string
     */
    protected $blockId;

    /**
     * @var string
     */
    protected static $urlContextKey = 'drup-blocks-context';

    /**
     * Les données du bloc sont contextualisées par rapport à la page où le bloc se trouve
     * (pour notamment pouvoir placer un même bloc sur différentes pages avec différentes valeurs)
     *
     * @var
     */
    protected $configContextValue;

    /**
     * Block storage config
     *
     * @var \Drupal\Core\Config\Config
     */
    protected $config;

    /**
     * Block storage config key (blockId.languageId.configContextValue)
     *
     * @var string
     */
    protected $configKey;

    /**
     * Bloc storage config values
     *
     * @var array
     */
    protected $configValues;

    /**
     * Ajax items form container
     *
     * @var string
     */
    protected $ajaxContainer = 'items';

    /**
     * Max count items for ajax form (-1 for infinite)
     *
     * @var int
     */
    protected $ajaxMaxRows = -1;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        $this->languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();

        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        if (isset($this->configuration['id'])) {
            $this->blockId = $this->configuration['id'];
            $this->config = \Drupal::service('config.factory')->getEditable('drup_blocks.admin_values');

            // Contexte de la page
            $currentView = DrupRequest::isAdminRoute() ? 'admin' : 'front';
            $this->configContextValue = $this->getConfigContext($currentView);

            // Config / values
            $this->configKey = $this->blockId . '.' . $this->languageId . '.' . $this->configContextValue;
            $this->configValues = $this->config->get($this->configKey);
        }

        return parent::defaultConfiguration();
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getConfigValue(string $key) {
        return $this->configValues[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getConfigValues() {
        return $this->configValues;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setConfigValue(string $key, $value) {
        $this->configValues[$key] = $value;
    }

    /**
     * @param array $values
     */
    public function setConfigValues(array $values) {
        $this->configValues = array_merge($this->configValues, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        parent::blockForm($form, $form_state);

        if (empty($this->configContextValue)) {
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

        $this->configValues = array_filter($this->configValues);

        $this->config->set($this->configKey, $this->configValues);
        $this->config->save();
    }

    /**
     * @return array|void
     */
    public function build() {}

    /**
     * A utiliser dans la methode build() pour le rendu du theme
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function mergeBuildParameters(array $parameters = []) {
        $adminUrl = null;

        if (\Drupal::currentUser()->hasPermission('administer blocks')) {
            $adminUrl = Url::fromRoute('entity.block.edit_form', ['block' => $this->blockId], [
                'query' => [
                    'destination' => \Drupal::destination()->get(),
                    self::$urlContextKey => $this->configContextValue
                ]
            ]);
        }
        $parameters = array_merge_recursive($parameters, [
            '#admin_url' => $adminUrl
        ]);
        return $parameters;
    }

    /**
     * A utiliser dans la methode blockForm() pour instancier un repeater
     *
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
            $itemIndex = $i;
            $values = $this->configValues[$this->ajaxContainer][$itemIndex] ?? [];

            $form[$this->ajaxContainer][$itemIndex] = [
                '#type' => 'details',
                '#collapsible' => true,
                '#open' => empty($values),
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
     *
     * @param array $rowContainer
     * @param array $rowValues
     */
    public function setAjaxRow(array &$rowContainer, array $rowValues) {
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
    protected function getConfigContext($view = 'admin') {
        // Depuis l'admin, le contexte est présent dans l'url en paramètre GET
        if ($view === 'admin') {
            return \Drupal::request()->query->get(self::$urlContextKey);
        }

        // Front : HP
        if (DrupRequest::isFront()) {
            return 'front';
        }

        // Front : Autre : depuis le type d'entité courante
        /** @var \Drupal\drup\DrupPageEntity $drupPageEntity */
        $drupPageEntity = \Drupal::service('drup_page_entity');
        if ($drupPageEntity->id()) {
            return $drupPageEntity->getEntityType() . '/' . $drupPageEntity->id();
        }

        return null;
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
}
