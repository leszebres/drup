<?php

namespace Drupal\drup\Helper;

/**
 * Class DrupTheme
 *
 * @package Drupal\drup\Helper
 */
abstract class DrupTheme {

    /**
     * Format $themes
     *
     * @param $themes
     * @param array $options
     */
    public static function format(&$themes, $options = []) {
        $options = \array_merge([
            'type' => 'blocks'
        ], $options);

        $themePath = \Drupal::theme()->getActiveTheme()->getPath();
        $themePathBlocks = $themePath . '/templates/' . $options['type'];

        foreach ($themes as $themeId => &$theme) {
            $template = null;

            if (\strpos($themeId, 'drup_' . $options['type']) !== false) {
                // Admin
                if (\strpos($themeId, 'drup_' . $options['type'] . '_admin') !== false) {
                    $template = \str_replace('drup_' . $options['type'] . '_admin_', '', $themeId);
                    $theme['variables']['admin_url'] = null;
                } else {
                    $template = \str_replace('drup_' . $options['type'] . '_', '', $themeId);
                }
            }

            if ($template === null) {
                $template = \str_replace('drup_', '', $themeId);
            }

            $theme['path'] = $themePathBlocks;
            $theme['template'] = \str_replace('_', '-', $template);
            $theme['variables']['theme_path'] = $themePath;
        }
        unset($theme);
    }

}
