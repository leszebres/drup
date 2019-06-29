<?php

namespace Drupal\drup_social_links\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drup\DrupSEO;
use Drupal\drup_social_links\DrupSocialLinks;

/**
 * Class RouterForm.
 */
class DrupSocialLinksForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [DrupSocialLinks::getConfigName()];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'drup_social_links_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['drup_social_links'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Link'),
                $this->t('Share'),
                $this->t('Title'),
                $this->t('Id'),
                $this->t('Link URL'),
                $this->t('Share URL'),
                $this->t('Options'),
                $this->t('Weight')
            ],
            '#empty' => $this->t('No links found'),
            '#tabledrag' => [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => 'form-item-weight'
                ]
            ]
        ];

        if ($config = DrupSocialLinks::getConfig()) {
            $links = $config->get('items');

            if (!empty($links)) {
                foreach ($links as $link) {
                    $form['drup_social_links'][] = $this->setRow($link);
                }
            }
        }

        $form['drup_social_links'][] = $this->setRow([
            'isNew' => true
        ]);

        $form['#attached']['library'][] = 'drup_social_links/form-links';

        $form['token_browser'] = [
            '#theme' => 'token_tree_link',
            '#token_types' => [DrupSEO::$tokenType],
            '#global_types' => true,
            '#show_nested' => false
        ];

        $help = '<br/><br/><hr/><br/>';
        $help .= '<h3>Exemples d\'urls de partage :</h3>';
        $help .= '<ul>';
            $help .= '<li>Facebook : https://www.facebook.com/sharer/sharer.php?u=[current-page:url]&t=[seo:meta:title]</li>';
            $help .= '<li>Twitter : https://twitter.com/share?url=[current-page:url]&text=[seo:meta:title]</li>';
            $help .= '<li>Linkedin : https://www.linkedin.com/shareArticle?url=[current-page:url]&title=[seo:meta:title]</li>';
            $help .= '<li>Email : mailto:?subject=[seo:meta:title]&body=[current-page:url]</li>';
            $help .= '<li>Pinterest : https://pinterest.com/pin/create/button/?url=[current-page:url]&description=[seo:meta:title]</li>';
        $help .= '</ul><br/><br/>';
        $form['help'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $help
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * @param $rowValues
     *
     * @return array
     */
    public function setRow($rowValues) {
        $row = [];
        $isNewRow = (isset($rowValues['isNew']) && $rowValues['isNew']);

        if (!$isNewRow) {
            $row['#attributes']['class'][] = 'draggable';
        }

        $row['link'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Link'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow ? $rowValues['link'] : false
        ];
        $row['share'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Share'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow ? $rowValues['share'] : false
        ];
        $row['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow ? $rowValues['title'] : null,
            '#size' => 10,
            '#attributes' => [
                'class' => ['form-item-title']
            ]
        ];
        $row['id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Id'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow ? $rowValues['id'] : null,
            '#size' => 10,
            '#attributes' => [
                'class' => ['form-item-id']
            ]
        ];
        $row['link_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Link URL'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow && !empty($rowValues['link_url']) ? $rowValues['link_url'] : null,
            '#size' => 30
        ];
        $row['share_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Share URL'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow && !empty($rowValues['share_url']) ? $rowValues['share_url'] : null,
            '#size' => 30
        ];
        $row['options'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Options'),
            '#title_display' => !$isNewRow ? 'invisible' : 'before',
            '#default_value' => !$isNewRow && !empty($rowValues['options']) ? $rowValues['options'] : null,
            '#placeholder' => $isNewRow ? 'key=value, key2=value2, etc' : null,
            '#size' => 20,
            '#attributes' => [
                'class' => ['form-item-options']
            ]
        ];
        $row['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => !$isNewRow && !empty($rowValues['weight']) ? $rowValues['weight'] : null,
            '#attributes' => [
                'class' => ['form-item-weight']
            ]
        ];

        foreach ($row as $rowItemId => $rowItem) {
            if (isset($rowItem['#type']) && $rowItem['#type'] === 'textfield' && empty($row[$rowItemId]['#placeholder'])) {
                $row[$rowItemId]['#placeholder'] = $rowItem['#title'];
            }
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = DrupSocialLinks::getConfig();
        $links = $form_state->getValue('drup_social_links');
        $saveLinks = [];

        if (!empty($links)) {
            foreach ($links as $link) {
                if (!empty($link['title']) && !empty($link['id'])) {
                    $saveLinks[$link['id']] = $link;
                }
            }
        }

        $config->set('items', $saveLinks);
        $config->save();

        parent::submitForm($form, $form_state);
    }
}
