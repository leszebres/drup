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
     * @var string
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
     * Retourne une valeur enregistrée dans la config du block
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getConfigValue(string $key) {
        return $this->configValues[$key] ?? null;
    }

    /**
     * Retourne l'ensemble des valeurs enregistrées dans la config du block
     *
     * @return array
     */
    public function getConfigValues() {
        return !empty($this->configValues) ? $this->configValues : [];
    }

    /**
     * Applique une config du block
     *
     * @param string $key
     * @param $value
     */
    public function setConfigValue(string $key, $value) {
        $this->configValues[$key] = $value;
    }

    /**
     * Applique un ensemble de configs du block
     *
     * @param $values
     */
    public function setConfigValues($values) {
        if (!empty($values) && \is_array($values)) {
            $this->configValues = array_merge($this->getConfigValues(), $values);
        }
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
        // spécificité pour le repeater ajax
        if ($ajaxRowsValues = $this->getConfigValue($this->ajaxContainer)) {
            $ajaxRowsValues = $ajaxRowsValues['values'];
            unset($ajaxRowsValues['actions']);

            // Enregistrements des données de chaque row
            if (!empty($ajaxRowsValues)) {
                foreach ($ajaxRowsValues as $index => $rowValues) {
                    if (!empty(\array_filter($rowValues['wrapper']))) {
                        $ajaxRowsValues[$index] = $rowValues['wrapper'] + ['weight' => $rowValues['weight']];
                    } else {
                        unset($ajaxRowsValues[$index]);
                    }
                }

                // Sort by weight
                \usort($ajaxRowsValues, static function ($a, $b) {
                    return (int) $a['weight'] > (int) $b['weight'];
                });
            }

            // Save
            $this->setConfigValue($this->ajaxContainer, $ajaxRowsValues);
        }

        // Global save
        $this->config->set($this->configKey, \array_filter($this->getConfigValues()));
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
        $ajaxItemsValues = $this->getConfigValue($this->ajaxContainer);

        if (!$form_state->has('ajax_items_index')) {
            $form_state->set('ajax_items_index', $ajaxItemsValues !== null ? \range(0, \count($ajaxItemsValues) - 1) : []);
        }

        // Main container
        $form['#tree'] = true;

        // Conteneur + Table avec des rows ajaxifiées
        $form[$this->ajaxContainer] = [
            '#type' => 'container', // ajout d'un conteneur intermédiaire permet de faire des #states sur $this->ajaxContainer
            'values' => [
                '#type' => 'table',
                '#header' => [
                    $this->t('Content'),
                    $this->t('Actions'),
                    $this->t('Weight')
                ],
                '#empty' => $this->t('No content found'),
                '#tabledrag' => [
                    [
                        'action' => 'order',
                        'relationship' => 'sibling',
                        'group' => 'form-item-weight'
                    ]
                ],
                '#prefix' => '<div id="ajax-items-fieldset-wrapper">',
                '#suffix' => '</div>',
            ]
        ];

        // Construct rows
        foreach ($form_state->get('ajax_items_index') as $itemIndex => $item) {
            $values = $ajaxItemsValues[$itemIndex] ?? [];

            $form[$this->ajaxContainer]['values'][$itemIndex] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['draggable']
                ]
            ];
            $form[$this->ajaxContainer]['values'][$itemIndex]['wrapper'] = [
                '#type' => 'container',
                '#title' => $this->t('Item') . ' #' . ($itemIndex + 1),
            ];

            // Populate each row with values
            $this->setAjaxRow($form[$this->ajaxContainer]['values'][$itemIndex]['wrapper'], $values);

            // Actions to delete row
            $form[$this->ajaxContainer]['values'][$itemIndex]['actions'] = [
                '#type' => 'container'
            ];
            $form[$this->ajaxContainer]['values'][$itemIndex]['actions']['delete'] = [
                '#type' => 'submit',
                '#value' => t('Remove'),
                '#name' => 'op-delete-item-' . $itemIndex,
                '#submit' => [[$this, 'ajaxRemoveRow']],
                '#attributes' => [
                    'data-index' => $itemIndex
                ],
                '#ajax' => [
                    'callback' => [$this, 'ajaxCallback'],
                    'wrapper' => 'ajax-items-fieldset-wrapper',
                ]
            ];

            // Weight
            $form[$this->ajaxContainer]['values'][$itemIndex]['weight'] = [
                '#type' => 'weight',
                '#title' => $this->t('Weight'),
                '#title_display' => 'invisible',
                '#default_value' => !empty($values['weight']) ? $values['weight'] : 50,
                '#attributes' => [
                    'class' => ['form-item-weight']
                ]
            ];
        }

        // Main actions
        $form[$this->ajaxContainer]['values']['actions'] = [];
        $form[$this->ajaxContainer]['values']['actions']['submits'] = [
            '#type' => 'actions',
            '#wrapper_attributes' => [
                'colspan' => \count($form[$this->ajaxContainer]['values']['#header']),
            ]
        ];
        $form[$this->ajaxContainer]['values']['actions']['submits']['add_item'] = [
            '#type' => 'submit',
            '#value' => t('Add content'),
            '#submit' => [[$this, 'ajaxAddRow']],
            '#ajax' => [
                'callback' => [$this, 'ajaxCallback'],
                'wrapper' => 'ajax-items-fieldset-wrapper',
            ],
        ];
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
        $indexes = &$form_state->get('ajax_items_index');

        if ($this->ajaxMaxRows !== -1 && \count($indexes) >= $this->ajaxMaxRows) {
            \Drupal::messenger()->addWarning($this->t('You can manage only @count items.', ['@count' => $this->ajaxMaxRows]));
        } else {
            // Push new index
            $indexes[] = null;
        }

        $form_state->setRebuild();
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function ajaxRemoveRow(array &$form, FormStateInterface $form_state) {
        $input = $form_state->getTriggeringElement();

        // Remove row
        if (isset($input['#attributes']['data-index'])) {
            $indexes = &$form_state->get('ajax_items_index');
            unset($indexes[$input['#attributes']['data-index']]);
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
