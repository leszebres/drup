<?php

namespace Drupal\drup\Block;

/**
 * Class DrupBlock
 *
 * @package Drupal\drup\Block
 */
abstract class DrupBlock {
    
    /**
     * Format block theme
     *
     * @param $themes
     * @param array $options
     */
    public static function format(&$themes, $options = []) {
        $options = array_merge([
            'type' => 'blocks'
        ], $options);
        
        $themePath = '/' . drupal_get_path('theme', 'drup_theme');
        $themePathBlocks = $themePath . '/templates/' . $options['type'];
        
        foreach ($themes as $themeID => &$theme) {
            $template = null;

            if (strpos($themeID, 'drup_' . $options['type']) !== false) {
                // Admin
                if (strpos($themeID, 'drup_' . $options['type'] . '_admin') !== false) {
                    $template = str_replace('drup_' . $options['type'] . '_admin_', '', $themeID);
                    $theme['variables']['admin_url'] = null;
                }
                else {
                    $template = str_replace('drup_' . $options['type'] . '_', '', $themeID);
                }
            }

            if ($template === null) {
                $template = str_replace('drup_' , '', $themeID);
            }

            $theme['path'] = $themePathBlocks;
            $theme['template'] = str_replace('_', '-', strtolower($template));
            $theme['variables']['theme_path'] = $themePath;
        }
    }
    
    /**
     * Cache invalidation if following entities are updated
     * @return array
     */
    public static function getDefaultCacheTags() {
        return ['node_list', 'taxonomy_term_list', 'media_list', 'config:system'];
    }

    /**
     * Cache invalidation for following
     * @return array
     */
    public static function getDefaultCacheContexts() {
        return ['route'];
    }
}
