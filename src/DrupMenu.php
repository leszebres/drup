<?php

namespace Drupal\drup;

use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\drup\Entity\ContentEntityBase;
use Drupal\drup\Entity\Node;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Class DrupMenu
 *
 * @package Drupal\drup
 */
class DrupMenu {

    /**
     * @param $items
     * @param $languageId
     *
     * @return bool
     */
    public static function checkMenuItemTranslation(&$items, $languageId) {
        foreach ($items as $index => &$item) {
            if (($item['original_link'] instanceof MenuLinkInterface) && ($nid = self::getNidFromMenuItem($item)) && ($node = Node::load($nid)) && ($node instanceof Node)) {
                if (!ContentEntityBase::isAllowed($node, $languageId)) {
                    unset($items[$index]);
                } else if (($menuLinkEntity = self::loadLinkEntityByLink($item['original_link'])) && !self::checkEntityTranslation($menuLinkEntity, $languageId)) {
                    unset($items[$index]);
                }
            }
            if (count($item['below']) > 0) {
                self::checkMenuItemTranslation($item['below'], $languageId);
            }
        }

        return $items;
    }

    /**
     * @param $entity
     * @param $language
     *
     * @return bool
     */
    public static function checkEntityTranslation($entity, $language) {
        if (!empty($entity)) {
            return array_key_exists($language, $entity->getTranslationLanguages()) ? true : false;
        }

        return false;
    }

    /**
     * @param \Drupal\Core\Menu\MenuLinkInterface $menuLinkContentPlugin
     *
     * @return |null
     */
    public static function loadLinkEntityByLink(MenuLinkInterface $menuLinkContentPlugin) {
        $entity = null;

        if ($menuLinkContentPlugin instanceof MenuLinkContent) {
            $menu_link = explode(':', $menuLinkContentPlugin->getPluginId(), 2);
            $uuid = $menu_link[1];
            $entity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);
        }

        return $entity;
    }

    /**
     * @param $menuItem
     *
     * @return int|null
     */
    public static function getNidFromMenuItem($menuItem) {
        if (isset($menuItem['url']) && !$menuItem['url']->isExternal()) {
            $urlParameters = $menuItem['url']->getRouteParameters();

            if (isset($urlParameters['node'])) {
                return $urlParameters['node'];
            }
        }

        return null;
    }

    /**
     * Retourne les liens enfants d'un noeud
     *
     * @param $nid
     * @param string $menuName
     *
     * @return array
     */
    public static function getNodeChildren($nid, $menuName = 'main') {
        $navItems = [];
        $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
        $links = $menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $nid], $menuName);
        $rootMenuItem = array_pop($links);

        $menuParameters = new MenuTreeParameters();
        $menuParameters
            ->setMaxDepth(1)
            ->setRoot($rootMenuItem->getPluginId())
            ->excludeRoot()
            ->onlyEnabledLinks();
        $menuTreeService = \Drupal::service('menu.link_tree');

        $menuTree = $menuTreeService->load($menuName, $menuParameters);
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort']
        ];
        $tree = $menuTreeService->transform($menuTree, $manipulators);
        $menuItems = $menuTreeService->build($tree);
        $menuItems['#cache']['max-age'] = 0;

        if (!empty($menuItems['#items'])) {
            foreach ($menuItems['#items'] as $index => $item) {
                $navItems[$index] = $item;
            }
        }

        return $navItems;
    }
}
