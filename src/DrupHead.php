<?php

namespace Drupal\drup;

/**
 * Class DrupHead
 *
 * @package Drupal\drup
 */
class DrupHead {

    /**
     * @param array $variables
     * @param array $data
     * @param int $version
     * @param null $path
     */
    public static function addFavicons(&$variables, $data = [], $version = 1, $path = null) {
        if (empty($path)) {
            $path = '/' . \Drupal::theme()->getActiveTheme()->getPath() . '/images/favicons';
        }

        $metas = [
            'apple-touch-icon' => [
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'apple-touch-icon',
                    'sizes' => '180x180',
                    'href' => $path . '/apple-touch-icon.png?v=' . $version,
                ],
            ],
            'icon32x32' => [
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'icon',
                    'sizes' => '32x32',
                    'href' => $path . '/favicon-32x32.png?v=' . $version,
                ],
            ],
            'icon16x16' => [
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'icon',
                    'sizes' => '16x16',
                    'href' => $path . '/favicon-16x16.png?v=' . $version,
                ],
            ],
            'manifest' => [
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'manifest',
                    'href' => $path . '/site.webmanifest?v=' . $version,
                ],
            ],
        ];

        if (isset($data['color_mask_icon'])) {
            $metas['mask-icon'] = [
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'mask-icon',
                    'href' => $path . '/safari-pinned-tab.svg?v=' . $version,
                    'color' => $data['color_mask_icon']
                ]
            ];
        }

        $metas['shortcut'] = [
            '#tag' => 'meta',
            '#attributes' => [
                'rel' => 'shortcut icon',
                'href' => $path . '/favicon.ico?v=' . $version,
            ]
        ];

        if (isset($data['color_msapplication'])) {
            $metas['msapplication-TileColor'] = [
                '#tag' => 'meta',
                '#attributes' => [
                    'name' => 'msapplication-TileColor',
                    'content' => $data['color_msapplication']
                ]
            ];
            $metas['msapplication-config'] = [
                '#tag' => 'meta',
                '#attributes' => [
                    'name' => 'msapplication-config',
                    'content' => $path . '/browserconfig.xml?v=' . $version
                ]
            ];
        }
        if (isset($data['color_theme'])) {
            $metas['theme-color'] = [
                '#tag' => 'meta',
                '#attributes' => [
                    'name' => 'theme-color',
                    'content' => $data['color_theme'],
                ],
            ];
        }

        foreach ($metas as $metaName => $meta) {
            $variables['page']['#attached']['html_head'][] = [
                $meta,
                'favicons-' . $metaName,
            ];
        }
    }

    /**
     * Remove some head metas that invalidate W3C
     *
     * @param array $attachments
     */
    public static function removeHeaderLinks(&$attachments) {
        if (!isset($attachments['#attached']['html_head_link'])) {
            return;
        }
        // Array to unset.
        $unset_html_head_link = [
            'delete-form',
            'edit-form',
            'version-history',
            'revision',
            'display',
            'drupal:content-translation-overview',
            'drupal:content-translation-add',
            'drupal:content-translation-edit',
            'drupal:content-translation-delete',
            'devel-load',
            'devel-render',
            'devel-definition',
            'clone-form',
            'token-devel',
            'delete-multiple-form'
            //'shortlink',
            //'canonical'
        ];
        
        foreach ($attachments['#attached']['html_head_link'] as $key => $value) {
            if (isset($value[0]['rel']) && in_array($value[0]['rel'], $unset_html_head_link)) {
                unset($attachments['#attached']['html_head_link'][$key]);
            }
        }
    }
}
