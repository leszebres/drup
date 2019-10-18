<?php

namespace Drupal\drup_router;

use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Class DrupRouter
 *
 * @package Drupal\drup_router
 */
class DrupRouter {

    /**
     * @var array
     */
    protected $routes;

    /**
     * @var string
     */
    protected $defaultContext;

    /**
     * @var string
     */
    protected $currentLanguageId;

    /**
     * @var \Drupal\drup\DrupPageEntity
     */
    protected $entity;

    /**
     * DrupRouterService constructor.
     */
    public function __construct() {
        $this->routes = \Drupal::config('drup.routes')->get('routes');
        $this->defaultContext = \Drupal::languageManager()->getDefaultLanguage()->getId();
        $this->currentLanguageId = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $this->entity = \Drupal::service('drup_page_entity');
    }

    /**
     * @return array
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * Return specific route data by name
     * @param $routeName
     *
     * @return null
     */
    public function getRoute($routeName = null) {
        if (!empty($this->routes)) {
            if ($routeName === null) {
                $routeName = $this->getName();
            }
            foreach ($this->routes as $route) {
                if ($route['routeName'] === $routeName) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Get entity id attached to route name
     * @param $routeName
     * @param null $context
     *
     * @return null
     */
    public function getId($routeName, $context = null) {
        $context = $this->getContext($context);

        if ($route = $this->getRoute($routeName)) {
            return (string) !empty($route[$context]) ? $route[$context] : $route[$this->defaultContext];
        }

        return null;
    }

    /**
     * @param $routeName
     * @param null $context
     *
     * @return \Drupal\Core\Entity\EntityInterface|null
     */
    public function getEntity($routeName, $context = null) {
        if (($route = $this->getRoute($routeName)) && $routeEntityId = $this->getId($routeName, $context)) {
            return \Drupal::entityTypeManager()->getStorage($route['targetType'])->load($routeEntityId);
        }

        return null;
    }

    /**
     * Get url alias of given route name
     *
     * @param $routeName
     *
     * @param null $context
     *
     * @return null
     */
    public function getPath($routeName, $context = null) {
        if (($url = $this->getUrl($routeName, $context)) && $url instanceof Url) {
            return $url->toString();
        }

        return null;
    }

    /**
     * Get drupal entity uri of a given route name
     *
     * @param $routeName
     *
     * @param null $context
     *
     * @return null|string
     */
    public function getUri($routeName, $context = null) {
        if (($route = $this->getRoute($routeName)) && $routeEntityId = $this->getId($routeName, $context)) {
            return 'internal:/' . $route['targetType'] . '/' . $routeEntityId;
        }

        return null;
    }

    /**
     * Get Url entity from route name
     *
     * @param $routeName
     * @param null $context
     *
     * @return \Drupal\Core\Url|null
     */
    public function getUrl($routeName, $context = null) {
        $language = new Language(['id' => $this->getContext($context)]);

        if (($uri = $this->getUri($routeName, $context)) && ($url = Url::fromUri($uri, ['language' => $language])) && $url->isRouted()) {
            return $url;
        }

        return null;
    }

    /**
     * Return route name attached to a given drupal entity
     * @param null $entityId
     * @param null $entityType
     * @param null $context
     *
     * @return null
     */
    public function getName($entityId = null, $entityType = null, $context = null) {
        if ($entityId === null) {
            $entityId = (string) $this->entity->id();
        }
        if ($entityType === null) {
            $entityType = $this->entity->getEntityType();
        }

        if (!empty($entityId) && !empty($this->routes)) {
            foreach ($this->routes as $route) {
                if (($route['targetType'] === $entityType) && ($entityId === $this->getId($route['routeName'], $context))) {
                    return $route['routeName'];
                }
            }
        }

        return null;
    }

    /**
     * @param $routeName
     * @param null $context
     *
     * @return bool
     */
    public function isRoute($routeName, $context = null) {
        if (($route = $this->getRoute($routeName)) && $routeEntityId = $this->getId($routeName, $context)) {
            return (($this->entity->getEntityType() === $route['targetType']) && ((string) $this->entity->id() === $routeEntityId));
        }

        return false;
    }


    /**
     * @param null $context
     *
     * @return null|string
     */
    protected function getContext($context = null) {
        if ($context === null) {
            $context = $this->currentLanguageId;
        }

        return $context;
    }
}
