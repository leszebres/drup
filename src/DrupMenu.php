<?php

namespace Drupal\drup;

use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\drup\Entity\ContentEntityBase;
use Drupal\drup\Helper\DrupUrl;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Class DrupMenu
 *
 * @package Drupal\drup
 */
class DrupMenu {

    /**
     * Supprime les entrées non traduites d'un menu
     *
     * @param array $items
     * @param string $languageId
     *
     * @return array
     */
    public static function translate(&$items, $languageId) {
        foreach ($items as $index => &$item) {
            if (($item['original_link'] instanceof \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent) && ($entity = DrupUrl::loadEntity($item['url']))) {
                if (!ContentEntityBase::isAllowed($entity, $languageId)) {
                    unset($items[$index]);
                } else if (($entity = self::loadMenuItemEntityFromMenuLink($item['original_link'])) && !self::isMenuItemTranslated($entity, $languageId)) {
                    unset($items[$index]);
                }
            }
            if (count($item['below']) > 0) {
                self::translate($item['below'], $languageId);
            }
        }

        return $items;
    }

    /**
     * Vérifie la traduction d'un élément de menu
     *
     * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
     * @param string $language
     *
     * @return bool
     */
    public static function isMenuItemTranslated($entity, $language) {
        if ($entity !== null) {
            return array_key_exists($language, $entity->getTranslationLanguages()) ? true : false;
        }

        return false;
    }

    /**
     * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menuLinkContent
     *
     * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
     */
    public static function loadMenuItemEntityFromMenuLink(MenuLinkInterface $menuLinkContent) {
        $entity = null;

        if ($menuLinkContent instanceof MenuLinkContent) {
            //$entity = self::loadEntityFromPluginId($menuLinkContent->getPluginId());
            $entity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $menuLinkContent->getDerivativeId());
        }

        return $entity;
    }

    /**
     * Load l'entité d'item de menu via son plugin ID (menu_link_content:UUID)
     *
     * @param string $pluginId
     *
     * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
     */
    public static function loadMenuItemEntityFromPluginId($pluginId) {
        $menu_link = explode(':', $pluginId, 2);

        if (isset($menu_link[1])) {
            $uuid = $menu_link[1];
            return \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);
        }

        return null;
    }

    /**
     * @param $parentEntityId
     * @param string $menuName
     *
     * @return bool
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function isChildOf($parentEntityId, $menuName = 'main') {
        $menuTreeService = \Drupal::menuTree();

        $parameters = $menuTreeService->getCurrentRouteMenuTreeParameters($menuName);
        $activeTrail = $parameters->activeTrail;

        // Remove current item
        $activeTrail = array_reverse($activeTrail);
        array_pop($activeTrail);

        foreach ($activeTrail as $pluginId) {
            if ($menuLink = self::loadMenuItemEntityFromPluginId($pluginId)) {
                if ($entity = DrupUrl::loadEntity($menuLink->getUrlObject())) {
                    if ((string) $entity->id() === (string) $parentEntityId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    /**
     * Retourne les liens enfants
     *
     * @param null $nid Nid du contenu parent. Si null, on utilise l'item de menu courant
     * @param string $menuName
     * @param boolean $loadEntities Load l'entité Drupal associée à l'élément de menu
     *
     * @return array
     */
    public static function getChildren($nid = null, $menuName = 'main', $loadEntities = true) {
        $navItems = [];
        $menuTreeService = \Drupal::menuTree();

        if ($nid === null) {
            // This one will give us the active trail in *reverse order*.
            // Our current active link always will be the first array element.
            $parameters = $menuTreeService->getCurrentRouteMenuTreeParameters($menuName);
            $activeTrail = array_keys($parameters->activeTrail);
            // But actually we need its parent.
            // Except for <front>. Which has no parent.
            $parentLinkId = $activeTrail[0];
        } else {
            $parameters = new MenuTreeParameters();
            $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
            $links = $menuLinkManager->loadLinksByRoute('entity.node.canonical', ['node' => $nid], $menuName);
            /** @var MenuLinkContent $rootMenuItem */
            $rootMenuItem = array_pop($links);
            $parentLinkId = $rootMenuItem->getPluginId();
        }

        // Having the parent now we set it as starting point to build our custom
        // tree.
        $parameters->setRoot($parentLinkId);
        $parameters->setMaxDepth(1);
        $parameters->onlyEnabledLinks();
        $parameters->excludeRoot();
        $menuTree = $menuTreeService->load($menuName, $parameters);

        // Optional: Native sort and access checks.
        $manipulators = [
            //['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];

        $tree = $menuTreeService->transform($menuTree, $manipulators);
        $menuItems = $menuTreeService->build($tree);
        $menuItems['#cache']['max-age'] = 0;

        if (!empty($menuItems['#items'])) {
            foreach ($menuItems['#items'] as $index => $item) {
                $navItems[$index] = $item;

                if ($loadEntities && ($entity = DrupUrl::loadEntity($item['url']))) {
                    /** @var ContentEntityBase $entity */
                    $navItems[$index]['entity'] = $entity;
                }
            }
        }

        return $navItems;
    }

    /**
     * Retourne les liens frères
     *
     * @param string $menuName
     *
     * @return array
     */
    public static function getSiblings($menuName = 'main') {
        $navItems = [];
        $menuTreeService = \Drupal::menuTree();

        // This one will give us the active trail in *reverse order*.
        // Our current active link always will be the first array element.
        $parameters   = $menuTreeService->getCurrentRouteMenuTreeParameters($menuName);
        $activeTrail = array_keys($parameters->activeTrail);

        // But actually we need its parent.
        // Except for <front>. Which has no parent.
        $parentLinkId = $activeTrail[1] ?? $activeTrail[0];

        // Having the parent now we set it as starting point to build our custom
        // tree.
        $parameters->setRoot($parentLinkId);
        $parameters->setMaxDepth(1);
        $parameters->excludeRoot();
        $menuTree = $menuTreeService->load($menuName, $parameters);

        // Optional: Native sort and access checks.
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
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
