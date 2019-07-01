<?php

namespace Drupal\drup;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuTreeParameters;
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
                if (!\Drupal\drup\Entity\ContentEntityBase::isAllowed($entity, $languageId)) {
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
     * Vérifie une entité a pour parent une autre entité
     *
     * @param $parentEntityId
     * @param \Drupal\Core\Entity\EntityInterface|null $currentEntity
     * @param string $menuName
     *
     * @return bool
     */
    public static function isChildOf($parentEntityId, EntityInterface $currentEntity = null, $menuName = 'main') {
        $parents = self::getParents($currentEntity, null, $menuName, true);

        if (!empty($parents)) {
            foreach ($parents as $index => $parent) {
                if (isset($parent['entity']) && (string) $parentEntityId === (string) $parent['entity']->id()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Récupère le parent direct
     *
     * @param \Drupal\Core\Entity\EntityInterface|null $currentEntity
     * @param string $menuName
     * @param bool $loadEntity
     *
     * @return array|mixed
     */
    public static function getParent(EntityInterface $currentEntity = null, $menuName = 'main', $loadEntity = true) {
        $parents = self::getParents($currentEntity, 1, $menuName, $loadEntity);
        return !empty($parents) ? current($parents) : [];
    }

    /**
     * @param \Drupal\Core\Entity\EntityInterface|null $currentEntity
     * @param null $maxDepth
     * @param string $menuName
     * @param bool $loadEntities
     *
     * @return array
     */
    public static function getParents(EntityInterface $currentEntity = null, $maxDepth = null, $menuName = 'main', $loadEntities = true) {
        $menuTreeService = \Drupal::menuTree();

        if ($currentEntity === null) {
            // This one will give us the active trail in *reverse order*.
            // Our current active link always will be the first array element.
            $parameters = $menuTreeService->getCurrentRouteMenuTreeParameters($menuName);
            //$activeTrail = array_keys($parameters->activeTrail);
            $activeTrail = $parameters->activeTrail;
        } else {
            $parameters = new MenuTreeParameters();
            /** @var \Drupal\Core\Menu\MenuLinkManager $menuLinkManager */
            $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
            $links = $menuLinkManager->loadLinksByRoute($currentEntity->toUrl()->getRouteName(), $currentEntity->toUrl()->getRouteParameters(), $menuName);
            $activeTrail = $menuLinkManager->getParentIds(current(array_keys($links)));
        }

        $parameters->setActiveTrail($activeTrail);
        $parameters->onlyEnabledLinks();
        $parameters->setMaxDepth(null);

        $menuTree = $menuTreeService->load($menuName, $parameters);

        // Optional: Native sort and access checks.
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];

        $tree = $menuTreeService->transform($menuTree, $manipulators);
        $menuItems = $menuTreeService->build($tree);
        $menuItems['#cache']['max-age'] = 0;

        $navItems = self::extractActiveTrail($menuItems['#items'], $menuItemsInActiveTrail);
        array_pop($navItems);

        if ($maxDepth !== null) {
            $navItems = array_slice($navItems, count($navItems) - $maxDepth, $maxDepth);
        }

        if ($loadEntities === true && !empty($menuItemsInActiveTrail)) {
            foreach ($navItems as $index => $navItem) {
                if ($loadEntities && ($entity = DrupUrl::loadEntity($navItem['url']))) {
                    /** @var ContentEntityBase $entity */
                    $navItems[$index]['entity'] = $entity;
                }
            }
        }

        return $navItems;
    }


    /**
     * Retourne les liens enfants
     *
     * @param \Drupal\Core\Entity\EntityInterface|null $currentEntity
     * @param string $menuName
     * @param bool $loadEntities
     *
     * @return array
     */
    public static function getChildren(EntityInterface $currentEntity = null, $menuName = 'main', $loadEntities = true) {
        $navItems = [];
        $menuTreeService = \Drupal::menuTree();

        if ($currentEntity === null) {
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
            $links = $menuLinkManager->loadLinksByRoute($currentEntity->toUrl()->getRouteName(), $currentEntity->toUrl()->getRouteParameters(), $menuName);
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

                if ($loadEntities && ($childEntity = DrupUrl::loadEntity($item['url']))) {
                    /** @var ContentEntityBase $entity */
                    $navItems[$index]['entity'] = $childEntity;
                }
            }
        }

        return $navItems;
    }

    /**
     * @todo currentEntity
     * Retourne les liens frères
     *
     * @param string $menuName
     *
     * @return array
     */
    public static function getSiblings(EntityInterface $currentEntity = null,$menuName = 'main', $loadEntities = true) {
        $navItems = [];
        $menuTreeService = \Drupal::menuTree();

        if ($currentEntity === null) {
            // This one will give us the active trail in *reverse order*.
            // Our current active link always will be the first array element.
            $parameters  = $menuTreeService->getCurrentRouteMenuTreeParameters($menuName);
            $activeTrail = array_keys($parameters->activeTrail);

            // But actually we need its parent.
            // Except for <front>. Which has no parent.
            $parentLinkId = $activeTrail[1] ?? $activeTrail[0];
        } else {
            $parameters = new MenuTreeParameters();
            $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
            $links = $menuLinkManager->loadLinksByRoute($currentEntity->toUrl()->getRouteName(), $currentEntity->toUrl()->getRouteParameters(), $menuName);
            /** @var MenuLinkContent $rootMenuItem */
            $activeTrail = $menuLinkManager->getParentIds(current(array_keys($links)));
            $parentLinkId = array_pop($activeTrail);
        }

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

                if ($loadEntities && ($entity = DrupUrl::loadEntity($item['url']))) {
                    /** @var ContentEntityBase $entity */
                    $navItems[$index]['entity'] = $entity;
                }
            }
        }

        return $navItems;
    }

    /**
     * @param $treeItems
     * @param $itemsInActiveTrail
     *
     * @return array
     */
    protected static function extractActiveTrail($treeItems, &$itemsInActiveTrail) {
        if (is_array($treeItems)) {
            foreach ($treeItems as $index => $treeItem) {
                if ($treeItem['in_active_trail'] === true) {
                    $itemsInActiveTrail[] = $treeItem;

                    if (!empty($treeItem['below'])) {
                        self::extractActiveTrail($treeItem['below'], $itemsInActiveTrail);
                    }
                    break;
                }
            }
        }

        return $itemsInActiveTrail;
    }
}
