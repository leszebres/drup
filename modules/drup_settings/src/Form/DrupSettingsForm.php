<?php

namespace Drupal\drup_settings\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\drup\Media\DrupFile;
use Drupal\drup_settings\DrupSettings;
use Drupal\drup_social_links\DrupSocialLinks;
use Drupal\user\Entity\User;

/**
 * Class DrupSettingsFrom.
 */
class DrupSettingsForm extends ConfigFormBase {

    /**
     * @var DrupSettings
     */
    protected $drupSettings;

    /**
     * @var string 
     */
    protected $formContainer = 'container';

    /**
     * Stock le contexte (langue) de DrupSettings de chaque form item
     *
     * @var array
     */
    protected $formItemsData = [];

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'admin_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            DrupSettings::getConfigValuesName(),
            DrupSettings::getConfigContextsName()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $this->drupSettings = \Drupal::service('drup_settings');

        $form[$this->formContainer] = [
            '#type' => 'horizontal_tabs',
            '#prefix' => '<div id="drup-settings-wrapper">',
            '#suffix' => '</div>',
            '#group_name' => 'drup_settings',
            '#entity_type' => 'drup_settings',
            '#bundle' => 'drup_settings'
        ];

        /**
         * MAIN SETTINGS
         */
        $form[$this->formContainer]['main'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuration du site'),
            '#collapsible' => true,
            '#collapsed' => true
        ];

        $form[$this->formContainer]['main']['site_information'] = [
            '#type' => 'details',
            '#title' => $this->t('Site details'),
            '#open' => true,
        ];
        $form[$this->formContainer]['main']['site_information']['site_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Site name'),
            '#required' => true,
            '#drup_context' => null
        ];
        $form[$this->formContainer]['main']['site_information']['site_slogan'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Slogan'),
            '#drup_context' => 'und'
        ];
        $form[$this->formContainer]['main']['site_information']['site_emails_from'] = [
            '#type' => 'email',
            '#title' => $this->t('Send mails with:'),
            '#required' => true,
            '#drup_context' => 'und'
        ];

        /* SEO */
        $form[$this->formContainer]['main']['site_seo'] = [
            '#type' => 'details',
            '#title' => $this->t('Référencement global'),
            '#open' => true
        ];
        $form[$this->formContainer]['main']['site_seo']['site_logo_alt'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Attribut alt du logo'),
            '#drup_context' => null
        ];
        $form[$this->formContainer]['main']['site_seo']['site_tag_manager'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID Google Tag Manager'),
            '#drup_context' => null
        ];


        $form[$this->formContainer]['main']['home_seo'] = [
            '#type' => 'details',
            '#title' => $this->t('Référencement de la page d\'accueil'),
            '#open' => true
        ];
        $form[$this->formContainer]['main']['home_seo']['home_meta_title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Meta Title'),
            '#drup_context' => null
        ];
        $form[$this->formContainer]['main']['home_seo']['home_meta_desc'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Meta Description'),
            '#drup_context' => null,
            '#maxlength' => 250
        ];
        $form[$this->formContainer]['main']['home_seo']['home_h1'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Titre principal H1'),
            '#drup_context' => null
        ];


        $socialNetworks = DrupSocialLinks::getLinkItems();
        if (!empty($socialNetworks)) {
            $form[$this->formContainer]['main']['social_networks'] = [
                '#type' => 'details',
                '#title' => $this->t('URLs des réseaux sociaux'),
                '#open' => true
            ];
            foreach ($socialNetworks as $network) {
                $form[$this->formContainer]['main']['social_networks']['site_social_link_' . $network['id']] = [
                    '#type' => 'url',
                    '#title' => $network['title'],
                    '#default_value' => $network['link_url']->toString()
                ];
            }
        }


        /**
         * FEATURES
         */
        $isSuperAdmin = User::load(\Drupal::currentUser()->id())->hasRole('super_admin');
        $form[$this->formContainer]['features'] = [
            '#type' => 'details',
            '#title' => $this->t('Features'),
            '#collapsible' => true,
            '#collapsed' => true,
            '#disabled' => !$isSuperAdmin
        ];
        $form[$this->formContainer]['features']['default_list_image'] = [
            '#type' => 'entity_autocomplete',
            '#target_type' => 'media',
            '#selection_settings' => [
                'target_bundles' => ['image']
            ],
            '#title' => $this->t('Média représentant l\'image par défaut dans les listes de contenus'),
            '#drup_context' => 'und'
        ];


        return parent::buildForm($form, $form_state);
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $drupSocialLinksConfig = DrupSocialLinks::getConfig();
        $drupSocialLinksItems = $drupSocialLinksConfig->get('items');

        foreach ($values as $key => $value) {
            // Custom social links
            if (strpos($key, 'site_social_link_') !== false) {
                $socialId = str_replace('site_social_link_', '', $key);

                if (isset($drupSocialLinksItems[$socialId])) {
                    $drupSocialLinksItems[$socialId]['link_url'] = $value;
                    $drupSocialLinksConfig->set('items', $drupSocialLinksItems);
                    $drupSocialLinksConfig->save();
                }
            } else {
                if (isset($this->formItemsData[$key])) {
                    $this->drupSettings->set($key, $value, $this->formItemsData[$key]->context);

                    // Files
                    if (!empty($value) && in_array($this->formItemsData[$key]->type, ['managed_file'])) {
                        DrupFile::setPermanent($value);
                    }
                }
            }
        }

        parent::submitForm($form, $form_state);
    }

    /**
     * Ajoute les default_value + description des champs automatiquement selon la clé #drup_context
     * @param $form
     * @param $form_state
     */
    protected function populateDefaultValues(&$items, $form_state) {
        if (is_array($items)) {
            foreach ($items as $key => &$item) {
                if (is_array($item) && array_key_exists('#drup_context', $item) && empty($item['#default_value'])) {

                    // Save info about current form item
                    $this->formItemsData[$key] = (object) [
                        'context' => $item['#drup_context'],
                        'type' => $item['#type']
                    ];

                    // Set default value
                    $item['#default_value'] = $this->drupSettings->getValue($key, $item['#drup_context']);

                    if ($item['#default_value'] !== null && $item['#type'] === 'entity_autocomplete') {
                        $item['#default_value'] = \Drupal::entityTypeManager()->getStorage($item['#target_type'])->load($item['#default_value']);
                    }

                    // UX description
                    $description = '<i>' . $this->t($item['#drup_context'] === 'und' ? 'Common to all languages' : 'Specific to each language') . '</i>';
                    if (!isset($item['#description'])) {
                        $item['#description'] = $description;
                    } else {
                        $item['#description'] = $description . '<br/>' . $item['#description'];
                    }
                }

                // Save #drup_context for each key
                $this->drupSettings->setContexts($this->formItemsData);

                $this->populateDefaultValues($item, $form_state);
            }
        }
    }
}
