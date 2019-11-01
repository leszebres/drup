<?php

namespace Drupal\drup\Block;

/**
 * Class DrupBlock
 *
 * @package Drupal\drup\Block
 */
abstract class DrupBlock {

    /**
     * Cache invalidation if following entities are updated
     *
     * @return array
     */
    public static function getDefaultCacheTags() {
        return ['node_list', 'taxonomy_term_list', 'media_list', 'config:system'];
    }

    /**
     * Cache invalidation for following
     *
     * @return array
     */
    public static function getDefaultCacheContexts() {
        return ['route'];
    }

}
